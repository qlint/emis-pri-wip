ALTER TABLE app.employee_cats ADD COLUMN active boolean NOT NULL DEFAULT true;
alter table app.employee_cats add creation_date timestamp without time zone NOT NULL DEFAULT now();
alter table app.employee_cats add created_by integer;
alter table app.employee_cats add modified_date timestamp without time zone;
alter table app.employee_cats add modified_by integer;


ALTER TABLE app.class_cats ADD COLUMN active boolean NOT NULL DEFAULT true;
alter table app.class_cats add creation_date timestamp without time zone NOT NULL DEFAULT now();
alter table app.class_cats add created_by integer;
alter table app.class_cats add modified_date timestamp without time zone;
alter table app.class_cats add modified_by integer;

alter table app.users add creation_date timestamp without time zone NOT NULL DEFAULT now();
alter table app.users add created_by integer;
alter table app.users add modified_date timestamp without time zone;
alter table app.users add modified_by integer;