-- turn on parent portal for the first time
--INSERT INTO app.settings(name,value) values('Parent Portal', 'Yes');

-- turn off parent portal
--UPDATE app.settings SET value = 'No' WHERE name = 'Parent Portal';

-- turn back on after inactiving parent portal
UPDATE app.settings SET value = 'Yes' WHERE name = 'Parent Portal';