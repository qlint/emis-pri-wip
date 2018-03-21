ALTER TABLE app.students ADD COLUMN student_type character varying DEFAULT 'Day Scholar';
ALTER TABLE app.students ADD COLUMN transport_route_id integer;
ALTER TABLE app.students ADD CONSTRAINT "FK_student_route" FOREIGN KEY (transport_route_id) REFERENCES app.transport_routes (transport_id) ON UPDATE NO ACTION ON DELETE NO ACTION;

insert into app.settings(name,value)
values('Student Types', 'Boarder,Day Scholar');