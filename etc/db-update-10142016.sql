BEGIN;
ALTER TABLE app.subjects ADD COLUMN use_for_grading boolean NOT NULL DEFAULT true;
ALTER TABLE app.invoices ADD COLUMN term_id integer;

CREATE OR REPLACE FUNCTION app.set_invoice_term()
  RETURNS boolean AS
$BODY$
declare
	_result record;
begin

for _result in 
		select inv_id, (select term_id from app.terms where due_date between start_date and end_date) as term_id
		from app.invoices
	loop
		update app.invoices set term_id = _result.term_id where inv_id = _result.inv_id;
	end loop;
	return true;
end;
$BODY$
  LANGUAGE plpgsql VOLATILE;


CREATE OR REPLACE VIEW app.invoice_balances2 AS 
 SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payment_inv_items.amount), 0::numeric) AS total_paid, COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount) AS balance, invoices.due_date, 
        CASE
            WHEN invoices.due_date < now()::date AND (COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount)) < 0::numeric THEN true
            ELSE false
        END AS past_due, invoices.canceled, invoices.term_id
   FROM app.invoices
   JOIN (app.invoice_line_items
   LEFT JOIN (app.payment_inv_items
   JOIN app.payments ON payment_inv_items.payment_id = payments.payment_id AND payments.reversed IS FALSE) ON invoice_line_items.inv_item_id = payment_inv_items.inv_item_id) ON invoices.inv_id = invoice_line_items.inv_id
  GROUP BY invoices.student_id, invoices.inv_id;
COMMIT;
select * from app.set_invoice_term();