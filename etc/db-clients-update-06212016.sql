ALTER TABLE app.classes DROP CONSTRAINT "U_class_name";

CREATE UNIQUE INDEX "U_active_class_name" ON app.classes (class_name, class_cat_id) WHERE active is true;

ALTER TABLE app.class_cats DROP CONSTRAINT "U_active_class_cat";

CREATE UNIQUE INDEX "U_active_class_cat" ON app.class_cats (class_cat_name) WHERE active is true;
