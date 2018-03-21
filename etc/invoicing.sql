SELECT
	student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,
	fee_item, student_fee_item_id, payment_method, frequency, num_payments,  yearly_amount, 
	(select sum(amount) from app.invoice_line_items where student_fee_item_id = q.student_fee_item_id) as total_amount_invoiced,	
	payment_plan_name, payment_interval,  payment_interval2,
	term_start_date, term_end_date, year_start_date, start_next_term, date_last_invoice
	
FROM (
	SELECT 
		students.student_id, first_name, middle_name, last_name,		
		fee_item, student_fee_items.student_fee_item_id, student_fee_items.payment_method, frequency,
		payment_plan_name, payment_interval, payment_interval2, coalesce(num_payments,1) as num_payments, 
		round( CASE WHEN frequency = 'per term' THEN student_fee_items.amount*3 ELSE student_fee_items.amount END, 2) as yearly_amount,
		(select start_date from app.current_term) as term_start_date,
		(select end_date from app.current_term) as term_end_date,
		coalesce((select start_date from app.next_term), (select end_date from app.current_term)) as start_next_term,
		(select min(start_date) from app.terms where date_part('year',start_date) = date_part('year', (select start_date from app.current_term)) ) as year_start_date,
		coalesce( (select max(due_date) from app.invoices where invoices.student_id = students.student_id), (select start_date from app.current_term)) as date_last_invoice,
		case when payment_plan_name = 'Per Month' then 4
		     else 1
		end as num_per_pay_period	
	FROM app.students									
	INNER JOIN app.student_fee_items
		INNER JOIN app.fee_items
		ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true									
	ON students.student_id = student_fee_items.student_id AND student_fee_items.active = true
	LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
	WHERE students.active = true
	ORDER BY students.student_id
) q