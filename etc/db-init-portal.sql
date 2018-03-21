CREATE TABLE app.blogs
(
   blog_id serial, 
   teacher_id integer NOT NULL, 
   class_id integer NOT NULL, 
   blog_name character varying NOT NULL,   
   CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id), 
   CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES app.employees (emp_id) ON UPDATE NO ACTION ON DELETE NO ACTION, 
   CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES app.classes (class_id) ON UPDATE NO ACTION ON DELETE NO ACTION
) 
WITH (
  OIDS = FALSE
)
;


CREATE TABLE app.blog_post_types
(
   post_type_id serial, 
   post_type character varying NOT NULL, 
   CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id)
) 
WITH (
  OIDS = FALSE
)
;

CREATE TABLE app.blog_post_statuses
(
  post_status_id serial NOT NULL,
  post_status character varying NOT NULL,
  CONSTRAINT "PK_post_status_id" PRIMARY KEY (post_status_id )
)
WITH (
  OIDS = FALSE
)
;

CREATE TABLE app.blog_posts
(
  post_id serial NOT NULL,
  blog_id integer NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  post_type_id integer,
  body text,
  title character varying NOT NULL,
  post_status_id integer NOT NULL,
  feature_image character varying,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "PK_post_id" PRIMARY KEY (post_id ),
  CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id)
      REFERENCES app.blogs (blog_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_post_status" FOREIGN KEY (post_status_id)
      REFERENCES app.blog_post_statuses (post_status_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id)
      REFERENCES app.blog_post_types (post_type_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);


CREATE TABLE app.homework
(
  homework_id serial NOT NULL,
  class_subject_id integer NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  due_date timestamp without time zone,
  assigned_date timestamp without time zone,
  body text,
  title character varying NOT NULL,
  post_status_id integer NOT NULL,
  attachment character varying,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id ),
  CONSTRAINT "FK_homework_class_subject" FOREIGN KEY (class_subject_id)
      REFERENCES app.class_subjects (class_subject_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_homework_post_status" FOREIGN KEY (post_status_id)
      REFERENCES app.blog_post_statuses (post_status_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);


CREATE TABLE app.communication_types
(
  com_type_id serial NOT NULL,
  com_type character varying NOT NULL,
  CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id )
)
WITH (
  OIDS=FALSE
);

CREATE TABLE app.communication_audience
(
  audience_id serial NOT NULL,
  audience character varying NOT NULL,
  CONSTRAINT "PK_audience_id" PRIMARY KEY (audience_id )
)
WITH (
  OIDS=FALSE
);


DROP TABLE app.news;
CREATE TABLE app.communications
(
  com_id serial NOT NULL,
  com_date date,
  audience_id integer NOT NULL,
  com_type_id integer NOT NULL,
  post_status_id integer,
  subject character varying,
  message character varying NOT NULL,
  attachment character varying,
  message_from integer NOT NULL,
  student_id integer,
  guardian_id integer,
  class_id integer,
  send_as_email boolean,
  send_as_sms boolean,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  reply_to character varying,
  sent boolean NOT NULL DEFAULT false,
  sent_date timestamp without time zone,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "PK_com_id" PRIMARY KEY (com_id ),
  CONSTRAINT "FK_audience_id" FOREIGN KEY (audience_id)
      REFERENCES app.communication_audience (audience_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id)
      REFERENCES app.classes (class_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id)
      REFERENCES app.guardians (guardian_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from)
      REFERENCES app.employees (emp_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id)
      REFERENCES app.students (student_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id)
      REFERENCES app.communication_types (com_type_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "FK_email_post_status" FOREIGN KEY (post_status_id)
      REFERENCES app.blog_post_statuses (post_status_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);



INSERT INTO app.blog_post_types VALUES (2, 'Homework');
INSERT INTO app.blog_post_types VALUES (1, 'General');

SELECT setval('app.blog_post_types_post_type_id_seq', 2, true);


INSERT INTO app.blog_post_statuses VALUES (2, 'Draft');
INSERT INTO app.blog_post_statuses VALUES (1, 'Published');

SELECT setval('app.blog_post_statuses_post_status_id_seq', 2, true);


INSERT INTO app.communication_audience VALUES (2, 'Class Specific'); -- parents in specific class
INSERT INTO app.communication_audience VALUES (1, 'School Wide'); -- all parents and staff
INSERT INTO app.communication_audience VALUES (3, 'All Staff'); -- all staff
INSERT INTO app.communication_audience VALUES (4, 'All Teachers'); -- all teachers
INSERT INTO app.communication_audience VALUES (5, 'Parent'); -- parent(s) of specific child

SELECT setval('app.communication_audience_audience_id_seq', 5, true);


INSERT INTO app.communication_types VALUES (2, 'Event'); -- flag that date is needed
INSERT INTO app.communication_types VALUES (1, 'General'); -- general messages
INSERT INTO app.communication_types VALUES (3, 'Important Notice'); -- flags as important when displayed
INSERT INTO app.communication_types VALUES (4, 'Reminder'); -- flags as reminder
INSERT INTO app.communication_types VALUES (5, 'Student Feedback'); -- 

SELECT setval('app.communication_audience_audience_id_seq', 5, true);
