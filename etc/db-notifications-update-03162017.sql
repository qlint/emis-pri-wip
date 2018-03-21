CREATE TABLE notifications
(
  notification_id serial NOT NULL,
  subdomain character varying NOT NULL,
  device_user_ids character varying[], -- up to 2000 device ids
  message character varying NOT NULL,
  sent boolean NOT NULL DEFAULT false,
  result boolean NOT NULL DEFAULT false,
  response character varying,
  CONSTRAINT "PK_notification_id" PRIMARY KEY (notification_id )
)
WITH (
  OIDS=FALSE
);
ALTER TABLE notifications
  OWNER TO postgres;
COMMENT ON COLUMN notifications.device_user_ids IS 'up to 2000 device ids';

