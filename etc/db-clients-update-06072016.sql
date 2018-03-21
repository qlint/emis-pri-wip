ALTER TABLE app.report_cards ADD COLUMN teacher_id integer;

ALTER TABLE app.class_cats ADD CONSTRAINT "U_active_class_cat" UNIQUE(class_cat_name , active );

ALTER TABLE app.employee_cats ADD CONSTRAINT "U_active_emp_cat" UNIQUE (emp_cat_name, active);
