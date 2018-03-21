
CREATE TABLE parents
(
  parent_id serial NOT NULL,
  first_name character varying NOT NULL,
  middle_name character varying,
  last_name character varying NOT NULL,
  email character varying NOT NULL,
  id_number character varying NOT NULL,
  username character varying NOT NULL,
  password character varying NOT NULL,
  active boolean NOT NULL DEFAULT true,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  modified_date timestamp without time zone,
  CONSTRAINT "PK_parents_parent_id" PRIMARY KEY (parent_id ),
  CONSTRAINT "U_id_number" UNIQUE (id_number ),
  CONSTRAINT "U_username" UNIQUE (username )
)
WITH (
  OIDS=FALSE
);



CREATE TABLE parent_students
(
  parent_student_id serial,
  parent_id integer NOT NULL,
  guardina_id integer NOT NULL,  
  student_id integer NOT NULL,
  subdomain character varying NOT NULL,
  dbusername character varying NOT NULL,
  dbpassword character varying NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  created_by integer,
  modified_date timestamp without time zone,
  modified_by integer,
  CONSTRAINT "parent_students_parent_student_id" PRIMARY KEY (parent_student_id ),
  CONSTRAINT "FK_parent_students_parent" FOREIGN KEY (parent_id )
      REFERENCES parents (parent_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);