CREATE TABLE app.communication_emails
(
  email_id serial NOT NULL,
  com_id integer NOT NULL,
  email_address character varying NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  send_date timestamp without time zone,
  forwarded boolean NOT NULL DEFAULT false,
  CONSTRAINT "PK_email_id" PRIMARY KEY (email_id ),
  CONSTRAINT "FK_comm_email_comm" FOREIGN KEY (com_id)
      REFERENCES app.communications (com_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE app.communication_emails
  OWNER TO postgres;


CREATE TABLE app.communication_sms
(
  sms_id serial NOT NULL,
  com_id integer NOT NULL,
  sim_number numeric NOT NULL,
  creation_date timestamp without time zone NOT NULL DEFAULT now(),
  send_date timestamp without time zone,
  forwarded boolean NOT NULL DEFAULT false,
  CONSTRAINT "PK_sms_id" PRIMARY KEY (sms_id ),
  CONSTRAINT "FK_comm_sms_comm" FOREIGN KEY (com_id)
      REFERENCES app.communications (com_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE app.communication_sms
  OWNER TO postgres;
