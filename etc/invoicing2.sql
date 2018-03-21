SELECT * FROM (
	SELECT
		student_id,  student_fee_item_id, student_name, fee_item, 
		coalesce((CASE 
			WHEN frequency = 'per term' and payment_method = 'Installments' THEN
				case when payment_plan_name = 'Per Month' then
					round(yearly_amount/9,2)
				else
					round(yearly_amount/num_payments,2)
				end
			ELSE
				round(yearly_amount,2)				
		END),0) AS invoice_amount,
		
		CASE WHEN payment_method = 'Installments' THEN
			case when num_payments_this_term > 1 THEN
				generate_series(date_last_invoice, date_last_invoice + ((payment_interval*(num_payments_this_term-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date
			else
				term_start_date
			end 
		     ELSE
			term_start_date								
		END as due_date,
		
		coalesce(round((select sum(amount)  
				from app.invoices 
				inner join app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id 
				where invoices.canceled = false 
				and student_fee_item_id = q2.student_fee_item_id 
				and due_date between date_last_invoice AND start_next_term
			)/num_payments_this_term,2) ,0)
		 as total_amount_invoiced,
		
		num_payments_this_term
		
	FROM (
		SELECT
			student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,
			fee_item, student_fee_item_id, payment_method, frequency,yearly_amount,num_payments,term_start_date,payment_plan_name,
			payment_interval,year_start_date,term_end_date,start_next_term,payment_interval2,
			CASE 
			WHEN frequency = 'per term' and payment_method = 'Installments' THEN
				CASE WHEN payment_plan_name = '50/50 Installment' THEN
					-- if 50/50 and not paid in first term, invoice
					CASE WHEN num_invoices = 0 THEN 
						(SELECT count(*) FROM (
							SELECT
							generate_series(term_start_date, term_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date  as due_date
							FROM (
								SELECT payment_interval,payment_interval2,num_payments
								FROM app.students
								INNER JOIN app.installment_options 
								ON installment_option_id = installment_options.installment_id
								WHERE student_id = q.student_id
							     ) q
						   )q2
						   WHERE due_date >= term_start_date and due_date < start_next_term
						)
					      ELSE 0 
					 END
				ELSE
					-- are there any installments due this term						
					(SELECT count(*) FROM (
						SELECT
						generate_series(date_last_invoice, date_last_invoice + ((payment_interval*(num_per_pay_period-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date as due_date
						FROM (
							SELECT payment_interval, payment_interval2,
							coalesce( (select max(due_date) from app.invoices where invoices.student_id = students.student_id), (select start_date from app.current_term)) as date_last_invoice
							FROM app.students
							INNER JOIN app.installment_options 
							ON installment_option_id = installment_options.installment_id
							WHERE student_id = q.student_id
						     ) q
						)q2
						WHERE due_date >= date_last_invoice and due_date < start_next_term
					)
				END
			ELSE
				-- otherwise we are paying annually, this is due in the first invoice
				CASE WHEN num_invoices = 0 
				THEN 1 
				ELSE 0 
				END
			END::integer AS num_payments_this_term,
			date_last_invoice,
			num_invoices,
			num_per_pay_period
		FROM (
			SELECT 
				students.student_id, first_name, middle_name, last_name, 
				fee_item, student_fee_items.student_fee_item_id, payment_interval,payment_interval2,
				student_fee_items.payment_method, frequency, coalesce(num_payments,1) as num_payments,payment_plan_name,
				round( CASE WHEN frequency = 'per term' THEN student_fee_items.amount*3 ELSE student_fee_items.amount END, 2) as yearly_amount,
				(select start_date from app.current_term) as term_start_date,
				(select end_date from app.current_term) as term_end_date,
				coalesce((select start_date from app.next_term), (select end_date from app.current_term)) as start_next_term,
				(select min(start_date) from app.terms where date_part('year',start_date) = date_part('year', (select start_date from app.current_term)) ) as year_start_date,
				coalesce( (select max(due_date) from app.invoices where invoices.student_id = students.student_id), (select start_date from app.current_term)) as date_last_invoice,
				case when payment_plan_name = 'Per Month' then 3
				     when payment_plan_name = 'Per Term' then 1
				end as num_per_pay_period,
				(SELECT count(*) FROM app.invoice_line_items 
					INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id 
					WHERE canceled = false 
					AND student_fee_item_id = student_fee_items.student_fee_item_id) as num_invoices
			FROM app.students									
			INNER JOIN app.student_fee_items
				INNER JOIN app.fee_items
				ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true									
			ON students.student_id = student_fee_items.student_id AND student_fee_items.active = true
			LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
			WHERE students.active = true
			ORDER BY students.student_id
		) q
		
	) q2
	WHERE q2.num_payments_this_term > 0
) q3
WHERE total_amount_invoiced < invoice_amount
ORDER BY student_id, due_date, fee_item