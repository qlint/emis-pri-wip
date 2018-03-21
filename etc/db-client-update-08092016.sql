CREATE OR REPLACE VIEW app.invoice_balances2 AS 
 SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payment_inv_items.amount), 0::numeric) AS total_paid, COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount) AS balance, invoices.due_date, 
        CASE
            WHEN invoices.due_date < now()::date AND (COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount)) < 0::numeric THEN true
            ELSE false
        END AS past_due, invoices.canceled
   FROM app.invoices
   JOIN (app.invoice_line_items
   LEFT JOIN (app.payment_inv_items
   JOIN app.payments ON payment_inv_items.payment_id = payments.payment_id AND payments.reversed IS FALSE) ON invoice_line_items.inv_item_id = payment_inv_items.inv_item_id) ON invoices.inv_id = invoice_line_items.inv_id
  GROUP BY invoices.student_id, invoices.inv_id;

ALTER TABLE app.invoice_balances2
  OWNER TO postgres;

CREATE TABLE app.credits
(
  credit_id serial NOT NULL,
  student_id integer NOT NULL,
  payment_id integer,
  amount numeric NOT NULL,
  amount_applied numeric NOT NULL DEFAULT 0,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "PK_credit_id" PRIMARY KEY (credit_id ),
  CONSTRAINT "FK_credit_payment" FOREIGN KEY (payment_id)
      REFERENCES app.payments (payment_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_credit_student" FOREIGN KEY (student_id)
      REFERENCES app.students (student_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

