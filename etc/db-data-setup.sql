﻿--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-12 15:06:42

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2073 (class 0 OID 62001)
-- Dependencies: 180 2074
-- Data for Name: settings; Type: TABLE DATA; Schema: app; Owner: postgres
--


-- settings
INSERT INTO settings VALUES ('Currency', 'Ksh');
INSERT INTO settings VALUES ('Marital Statuses', 'Married,Separated,Divorced,Widowed,Single');
INSERT INTO settings VALUES ('Guardian Relationships', 'Father,Mother,Guardian,Step Parent,Other');
INSERT INTO settings VALUES ('Student Categories', 'Regular,Scholership,Fully Sponsored');
INSERT INTO settings VALUES ('Medical Conditions', 'Allergies,Asthma,Convulsions,Diabetes,Disability,Ear Problems,Epilepsy,Eye Problems,Extreme Tiredness,Fainting Spells,Frequent Headaches,Memory Problems,Meningitis,Sleeping Problems,Other');
INSERT INTO settings VALUES ('Titles', 'Mr,Mrs,Ms,Dr');
INSERT INTO settings VALUES ('Initial Year', date_part('year',now()));
INSERT INTO settings VALUES ('Payment Methods', 'Cash,Cheque,Bank Transfer');
INSERT INTO settings VALUES ('Term Start Month', '1');
INSERT INTO settings VALUES ('Payment Options', 'Annually,Installments');
INSERT INTO settings VALUES ('Frequencies', 'per term,yearly,once');


-- installment options
INSERT INTO installment_options VALUES (1, 'Per Term', true, 3, 4, 'month');
INSERT INTO installment_options VALUES (2, 'Per Month', true, 12, 1, 'month');
SELECT setval('app.installment_options_installment_id_seq', 2, true);



-- exam types
INSERT INTO exam_types VALUES (1, 'Opener', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 1);
INSERT INTO exam_types VALUES (2, 'Mid Term', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 2);
INSERT INTO exam_types VALUES (3, 'End Term', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 3);
SELECT setval('app.exam_types_exam_type_id_seq', 3, true);



-- class cats
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (1, 'Baby Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (2, 'Nursery Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (3, 'Preunit Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (4, 'Lower Primary');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (5, 'Mid Primary');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (6, 'Upper Primary');
SELECT setval('app.class_cats_class_cat_id_seq', 6, true);


-- employee cats
INSERT INTO employee_cats VALUES (1, 'Teaching');
INSERT INTO employee_cats VALUES (2, 'Non-teaching');
SELECT setval('app.employee_cats_emp_cat_id_seq', 2, true);


-- countries
INSERT INTO countries VALUES (1, 'Afghanistan', 'AF', 'AFG', 1);
INSERT INTO countries VALUES (3, 'Algeria', 'DZ', 'DZA', 1);
INSERT INTO countries VALUES (4, 'American Samoa', 'AS', 'ASM', 1);
INSERT INTO countries VALUES (5, 'Andorra', 'AD', 'AND', 1);
INSERT INTO countries VALUES (6, 'Angola', 'AO', 'AGO', 1);
INSERT INTO countries VALUES (7, 'Anguilla', 'AI', 'AIA', 1);
INSERT INTO countries VALUES (8, 'Antarctica', 'AQ', 'ATA', 1);
INSERT INTO countries VALUES (9, 'Antigua and Barbuda', 'AG', 'ATG', 1);
INSERT INTO countries VALUES (10, 'Argentina', 'AR', 'ARG', 1);
INSERT INTO countries VALUES (11, 'Armenia', 'AM', 'ARM', 1);
INSERT INTO countries VALUES (12, 'Aruba', 'AW', 'ABW', 1);
INSERT INTO countries VALUES (13, 'Australia', 'AU', 'AUS', 1);
INSERT INTO countries VALUES (14, 'Austria', 'AT', 'AUT', 5);
INSERT INTO countries VALUES (15, 'Azerbaijan', 'AZ', 'AZE', 1);
INSERT INTO countries VALUES (16, 'Bahamas', 'BS', 'BHS', 1);
INSERT INTO countries VALUES (17, 'Bahrain', 'BH', 'BHR', 1);
INSERT INTO countries VALUES (18, 'Bangladesh', 'BD', 'BGD', 1);
INSERT INTO countries VALUES (19, 'Barbados', 'BB', 'BRB', 1);
INSERT INTO countries VALUES (20, 'Belarus', 'BY', 'BLR', 1);
INSERT INTO countries VALUES (21, 'Belgium', 'BE', 'BEL', 1);
INSERT INTO countries VALUES (22, 'Belize', 'BZ', 'BLZ', 1);
INSERT INTO countries VALUES (23, 'Benin', 'BJ', 'BEN', 1);
INSERT INTO countries VALUES (24, 'Bermuda', 'BM', 'BMU', 1);
INSERT INTO countries VALUES (25, 'Bhutan', 'BT', 'BTN', 1);
INSERT INTO countries VALUES (26, 'Bolivia', 'BO', 'BOL', 1);
INSERT INTO countries VALUES (27, 'Bosnia and Herzegowina', 'BA', 'BIH', 1);
INSERT INTO countries VALUES (28, 'Botswana', 'BW', 'BWA', 1);
INSERT INTO countries VALUES (29, 'Bouvet Island', 'BV', 'BVT', 1);
INSERT INTO countries VALUES (30, 'Brazil', 'BR', 'BRA', 1);
INSERT INTO countries VALUES (31, 'British Indian Ocean Territory', 'IO', 'IOT', 1);
INSERT INTO countries VALUES (32, 'Brunei Darussalam', 'BN', 'BRN', 1);
INSERT INTO countries VALUES (33, 'Bulgaria', 'BG', 'BGR', 1);
INSERT INTO countries VALUES (34, 'Burkina Faso', 'BF', 'BFA', 1);
INSERT INTO countries VALUES (35, 'Burundi', 'BI', 'BDI', 1);
INSERT INTO countries VALUES (36, 'Cambodia', 'KH', 'KHM', 1);
INSERT INTO countries VALUES (37, 'Cameroon', 'CM', 'CMR', 1);
INSERT INTO countries VALUES (38, 'Canada', 'CA', 'CAN', 1);
INSERT INTO countries VALUES (39, 'Cape Verde', 'CV', 'CPV', 1);
INSERT INTO countries VALUES (40, 'Cayman Islands', 'KY', 'CYM', 1);
INSERT INTO countries VALUES (41, 'Central African Republic', 'CF', 'CAF', 1);
INSERT INTO countries VALUES (42, 'Chad', 'TD', 'TCD', 1);
INSERT INTO countries VALUES (43, 'Chile', 'CL', 'CHL', 1);
INSERT INTO countries VALUES (44, 'China', 'CN', 'CHN', 1);
INSERT INTO countries VALUES (45, 'Christmas Island', 'CX', 'CXR', 1);
INSERT INTO countries VALUES (46, 'Cocos (Keeling) Islands', 'CC', 'CCK', 1);
INSERT INTO countries VALUES (47, 'Colombia', 'CO', 'COL', 1);
INSERT INTO countries VALUES (48, 'Comoros', 'KM', 'COM', 1);
INSERT INTO countries VALUES (49, 'Congo', 'CG', 'COG', 1);
INSERT INTO countries VALUES (50, 'Cook Islands', 'CK', 'COK', 1);
INSERT INTO countries VALUES (51, 'Costa Rica', 'CR', 'CRI', 1);
INSERT INTO countries VALUES (52, 'Cote D''Ivoire', 'CI', 'CIV', 1);
INSERT INTO countries VALUES (53, 'Croatia', 'HR', 'HRV', 1);
INSERT INTO countries VALUES (54, 'Cuba', 'CU', 'CUB', 1);
INSERT INTO countries VALUES (55, 'Cyprus', 'CY', 'CYP', 1);
INSERT INTO countries VALUES (56, 'Czech Republic', 'CZ', 'CZE', 1);
INSERT INTO countries VALUES (57, 'Denmark', 'DK', 'DNK', 1);
INSERT INTO countries VALUES (58, 'Djibouti', 'DJ', 'DJI', 1);
INSERT INTO countries VALUES (59, 'Dominica', 'DM', 'DMA', 1);
INSERT INTO countries VALUES (60, 'Dominican Republic', 'DO', 'DOM', 1);
INSERT INTO countries VALUES (61, 'East Timor', 'TP', 'TMP', 1);
INSERT INTO countries VALUES (62, 'Ecuador', 'EC', 'ECU', 1);
INSERT INTO countries VALUES (63, 'Egypt', 'EG', 'EGY', 1);
INSERT INTO countries VALUES (64, 'El Salvador', 'SV', 'SLV', 1);
INSERT INTO countries VALUES (65, 'Equatorial Guinea', 'GQ', 'GNQ', 1);
INSERT INTO countries VALUES (66, 'Eritrea', 'ER', 'ERI', 1);
INSERT INTO countries VALUES (67, 'Estonia', 'EE', 'EST', 1);
INSERT INTO countries VALUES (68, 'Ethiopia', 'ET', 'ETH', 1);
INSERT INTO countries VALUES (69, 'Falkland Islands (Malvinas)', 'FK', 'FLK', 1);
INSERT INTO countries VALUES (70, 'Faroe Islands', 'FO', 'FRO', 1);
INSERT INTO countries VALUES (71, 'Fiji', 'FJ', 'FJI', 1);
INSERT INTO countries VALUES (72, 'Finland', 'FI', 'FIN', 1);
INSERT INTO countries VALUES (73, 'France', 'FR', 'FRA', 1);
INSERT INTO countries VALUES (74, 'France, Metropolitan', 'FX', 'FXX', 1);
INSERT INTO countries VALUES (75, 'French Guiana', 'GF', 'GUF', 1);
INSERT INTO countries VALUES (76, 'French Polynesia', 'PF', 'PYF', 1);
INSERT INTO countries VALUES (77, 'French Southern Territories', 'TF', 'ATF', 1);
INSERT INTO countries VALUES (78, 'Gabon', 'GA', 'GAB', 1);
INSERT INTO countries VALUES (79, 'Gambia', 'GM', 'GMB', 1);
INSERT INTO countries VALUES (80, 'Georgia', 'GE', 'GEO', 1);
INSERT INTO countries VALUES (81, 'Germany', 'DE', 'DEU', 5);
INSERT INTO countries VALUES (82, 'Ghana', 'GH', 'GHA', 1);
INSERT INTO countries VALUES (83, 'Gibraltar', 'GI', 'GIB', 1);
INSERT INTO countries VALUES (84, 'Greece', 'GR', 'GRC', 1);
INSERT INTO countries VALUES (85, 'Greenland', 'GL', 'GRL', 1);
INSERT INTO countries VALUES (86, 'Grenada', 'GD', 'GRD', 1);
INSERT INTO countries VALUES (87, 'Guadeloupe', 'GP', 'GLP', 1);
INSERT INTO countries VALUES (88, 'Guam', 'GU', 'GUM', 1);
INSERT INTO countries VALUES (89, 'Guatemala', 'GT', 'GTM', 1);
INSERT INTO countries VALUES (90, 'Guinea', 'GN', 'GIN', 1);
INSERT INTO countries VALUES (91, 'Guinea-bissau', 'GW', 'GNB', 1);
INSERT INTO countries VALUES (92, 'Guyana', 'GY', 'GUY', 1);
INSERT INTO countries VALUES (93, 'Haiti', 'HT', 'HTI', 1);
INSERT INTO countries VALUES (94, 'Heard and Mc Donald Islands', 'HM', 'HMD', 1);
INSERT INTO countries VALUES (95, 'Honduras', 'HN', 'HND', 1);
INSERT INTO countries VALUES (96, 'Hong Kong', 'HK', 'HKG', 1);
INSERT INTO countries VALUES (97, 'Hungary', 'HU', 'HUN', 1);
INSERT INTO countries VALUES (98, 'Iceland', 'IS', 'ISL', 1);
INSERT INTO countries VALUES (99, 'India', 'IN', 'IND', 1);
INSERT INTO countries VALUES (100, 'Indonesia', 'ID', 'IDN', 1);
INSERT INTO countries VALUES (101, 'Iran (Islamic Republic of)', 'IR', 'IRN', 1);
INSERT INTO countries VALUES (102, 'Iraq', 'IQ', 'IRQ', 1);
INSERT INTO countries VALUES (103, 'Ireland', 'IE', 'IRL', 1);
INSERT INTO countries VALUES (104, 'Israel', 'IL', 'ISR', 1);
INSERT INTO countries VALUES (105, 'Italy', 'IT', 'ITA', 1);
INSERT INTO countries VALUES (106, 'Jamaica', 'JM', 'JAM', 1);
INSERT INTO countries VALUES (107, 'Japan', 'JP', 'JPN', 1);
INSERT INTO countries VALUES (108, 'Jordan', 'JO', 'JOR', 1);
INSERT INTO countries VALUES (109, 'Kazakhstan', 'KZ', 'KAZ', 1);
INSERT INTO countries VALUES (110, 'Kenya', 'KE', 'KEN', 1);
INSERT INTO countries VALUES (111, 'Kiribati', 'KI', 'KIR', 1);
INSERT INTO countries VALUES (112, 'Korea, Democratic People''s Republic of', 'KP', 'PRK', 1);
INSERT INTO countries VALUES (113, 'Korea, Republic of', 'KR', 'KOR', 1);
INSERT INTO countries VALUES (114, 'Kuwait', 'KW', 'KWT', 1);
INSERT INTO countries VALUES (115, 'Kyrgyzstan', 'KG', 'KGZ', 1);
INSERT INTO countries VALUES (116, 'Lao People''s Democratic Republic', 'LA', 'LAO', 1);
INSERT INTO countries VALUES (117, 'Latvia', 'LV', 'LVA', 1);
INSERT INTO countries VALUES (118, 'Lebanon', 'LB', 'LBN', 1);
INSERT INTO countries VALUES (119, 'Lesotho', 'LS', 'LSO', 1);
INSERT INTO countries VALUES (120, 'Liberia', 'LR', 'LBR', 1);
INSERT INTO countries VALUES (121, 'Libyan Arab Jamahiriya', 'LY', 'LBY', 1);
INSERT INTO countries VALUES (122, 'Liechtenstein', 'LI', 'LIE', 1);
INSERT INTO countries VALUES (123, 'Lithuania', 'LT', 'LTU', 1);
INSERT INTO countries VALUES (124, 'Luxembourg', 'LU', 'LUX', 1);
INSERT INTO countries VALUES (125, 'Macau', 'MO', 'MAC', 1);
INSERT INTO countries VALUES (126, 'Macedonia, The Former Yugoslav Republic of', 'MK', 'MKD', 1);
INSERT INTO countries VALUES (127, 'Madagascar', 'MG', 'MDG', 1);
INSERT INTO countries VALUES (128, 'Malawi', 'MW', 'MWI', 1);
INSERT INTO countries VALUES (129, 'Malaysia', 'MY', 'MYS', 1);
INSERT INTO countries VALUES (130, 'Maldives', 'MV', 'MDV', 1);
INSERT INTO countries VALUES (131, 'Mali', 'ML', 'MLI', 1);
INSERT INTO countries VALUES (132, 'Malta', 'MT', 'MLT', 1);
INSERT INTO countries VALUES (133, 'Marshall Islands', 'MH', 'MHL', 1);
INSERT INTO countries VALUES (134, 'Martinique', 'MQ', 'MTQ', 1);
INSERT INTO countries VALUES (135, 'Mauritania', 'MR', 'MRT', 1);
INSERT INTO countries VALUES (136, 'Mauritius', 'MU', 'MUS', 1);
INSERT INTO countries VALUES (137, 'Mayotte', 'YT', 'MYT', 1);
INSERT INTO countries VALUES (138, 'Mexico', 'MX', 'MEX', 1);
INSERT INTO countries VALUES (139, 'Micronesia, Federated States of', 'FM', 'FSM', 1);
INSERT INTO countries VALUES (140, 'Moldova, Republic of', 'MD', 'MDA', 1);
INSERT INTO countries VALUES (141, 'Monaco', 'MC', 'MCO', 1);
INSERT INTO countries VALUES (142, 'Mongolia', 'MN', 'MNG', 1);
INSERT INTO countries VALUES (143, 'Montserrat', 'MS', 'MSR', 1);
INSERT INTO countries VALUES (144, 'Morocco', 'MA', 'MAR', 1);
INSERT INTO countries VALUES (145, 'Mozambique', 'MZ', 'MOZ', 1);
INSERT INTO countries VALUES (146, 'Myanmar', 'MM', 'MMR', 1);
INSERT INTO countries VALUES (147, 'Namibia', 'NA', 'NAM', 1);
INSERT INTO countries VALUES (148, 'Nauru', 'NR', 'NRU', 1);
INSERT INTO countries VALUES (149, 'Nepal', 'NP', 'NPL', 1);
INSERT INTO countries VALUES (150, 'Netherlands', 'NL', 'NLD', 1);
INSERT INTO countries VALUES (151, 'Netherlands Antilles', 'AN', 'ANT', 1);
INSERT INTO countries VALUES (152, 'New Caledonia', 'NC', 'NCL', 1);
INSERT INTO countries VALUES (153, 'New Zealand', 'NZ', 'NZL', 1);
INSERT INTO countries VALUES (154, 'Nicaragua', 'NI', 'NIC', 1);
INSERT INTO countries VALUES (155, 'Niger', 'NE', 'NER', 1);
INSERT INTO countries VALUES (156, 'Nigeria', 'NG', 'NGA', 1);
INSERT INTO countries VALUES (157, 'Niue', 'NU', 'NIU', 1);
INSERT INTO countries VALUES (158, 'Norfolk Island', 'NF', 'NFK', 1);
INSERT INTO countries VALUES (159, 'Northern Mariana Islands', 'MP', 'MNP', 1);
INSERT INTO countries VALUES (160, 'Norway', 'NO', 'NOR', 1);
INSERT INTO countries VALUES (161, 'Oman', 'OM', 'OMN', 1);
INSERT INTO countries VALUES (162, 'Pakistan', 'PK', 'PAK', 1);
INSERT INTO countries VALUES (163, 'Palau', 'PW', 'PLW', 1);
INSERT INTO countries VALUES (164, 'Panama', 'PA', 'PAN', 1);
INSERT INTO countries VALUES (165, 'Papua New Guinea', 'PG', 'PNG', 1);
INSERT INTO countries VALUES (166, 'Paraguay', 'PY', 'PRY', 1);
INSERT INTO countries VALUES (167, 'Peru', 'PE', 'PER', 1);
INSERT INTO countries VALUES (168, 'Philippines', 'PH', 'PHL', 1);
INSERT INTO countries VALUES (169, 'Pitcairn', 'PN', 'PCN', 1);
INSERT INTO countries VALUES (170, 'Poland', 'PL', 'POL', 1);
INSERT INTO countries VALUES (171, 'Portugal', 'PT', 'PRT', 1);
INSERT INTO countries VALUES (172, 'Puerto Rico', 'PR', 'PRI', 1);
INSERT INTO countries VALUES (173, 'Qatar', 'QA', 'QAT', 1);
INSERT INTO countries VALUES (174, 'Reunion', 'RE', 'REU', 1);
INSERT INTO countries VALUES (175, 'Romania', 'RO', 'ROM', 1);
INSERT INTO countries VALUES (176, 'Russian Federation', 'RU', 'RUS', 1);
INSERT INTO countries VALUES (177, 'Rwanda', 'RW', 'RWA', 1);
INSERT INTO countries VALUES (178, 'Saint Kitts and Nevis', 'KN', 'KNA', 1);
INSERT INTO countries VALUES (179, 'Saint Lucia', 'LC', 'LCA', 1);
INSERT INTO countries VALUES (180, 'Saint Vincent and the Grenadines', 'VC', 'VCT', 1);
INSERT INTO countries VALUES (181, 'Samoa', 'WS', 'WSM', 1);
INSERT INTO countries VALUES (182, 'San Marino', 'SM', 'SMR', 1);
INSERT INTO countries VALUES (183, 'Sao Tome and Principe', 'ST', 'STP', 1);
INSERT INTO countries VALUES (184, 'Saudi Arabia', 'SA', 'SAU', 1);
INSERT INTO countries VALUES (185, 'Senegal', 'SN', 'SEN', 1);
INSERT INTO countries VALUES (186, 'Seychelles', 'SC', 'SYC', 1);
INSERT INTO countries VALUES (187, 'Sierra Leone', 'SL', 'SLE', 1);
INSERT INTO countries VALUES (188, 'Singapore', 'SG', 'SGP', 4);
INSERT INTO countries VALUES (189, 'Slovakia (Slovak Republic)', 'SK', 'SVK', 1);
INSERT INTO countries VALUES (190, 'Slovenia', 'SI', 'SVN', 1);
INSERT INTO countries VALUES (191, 'Solomon Islands', 'SB', 'SLB', 1);
INSERT INTO countries VALUES (192, 'Somalia', 'SO', 'SOM', 1);
INSERT INTO countries VALUES (193, 'South Africa', 'ZA', 'ZAF', 1);
INSERT INTO countries VALUES (194, 'South Georgia and the South Sandwich Islands', 'GS', 'SGS', 1);
INSERT INTO countries VALUES (195, 'Spain', 'ES', 'ESP', 3);
INSERT INTO countries VALUES (196, 'Sri Lanka', 'LK', 'LKA', 1);
INSERT INTO countries VALUES (197, 'St. Helena', 'SH', 'SHN', 1);
INSERT INTO countries VALUES (198, 'St. Pierre and Miquelon', 'PM', 'SPM', 1);
INSERT INTO countries VALUES (199, 'Sudan', 'SD', 'SDN', 1);
INSERT INTO countries VALUES (200, 'Suriname', 'SR', 'SUR', 1);
INSERT INTO countries VALUES (201, 'Svalbard and Jan Mayen Islands', 'SJ', 'SJM', 1);
INSERT INTO countries VALUES (202, 'Swaziland', 'SZ', 'SWZ', 1);
INSERT INTO countries VALUES (203, 'Sweden', 'SE', 'SWE', 1);
INSERT INTO countries VALUES (204, 'Switzerland', 'CH', 'CHE', 1);
INSERT INTO countries VALUES (205, 'Syrian Arab Republic', 'SY', 'SYR', 1);
INSERT INTO countries VALUES (206, 'Taiwan', 'TW', 'TWN', 1);
INSERT INTO countries VALUES (207, 'Tajikistan', 'TJ', 'TJK', 1);
INSERT INTO countries VALUES (208, 'Tanzania, United Republic of', 'TZ', 'TZA', 1);
INSERT INTO countries VALUES (209, 'Thailand', 'TH', 'THA', 1);
INSERT INTO countries VALUES (210, 'Togo', 'TG', 'TGO', 1);
INSERT INTO countries VALUES (211, 'Tokelau', 'TK', 'TKL', 1);
INSERT INTO countries VALUES (212, 'Tonga', 'TO', 'TON', 1);
INSERT INTO countries VALUES (213, 'Trinidad and Tobago', 'TT', 'TTO', 1);
INSERT INTO countries VALUES (214, 'Tunisia', 'TN', 'TUN', 1);
INSERT INTO countries VALUES (215, 'Turkey', 'TR', 'TUR', 1);
INSERT INTO countries VALUES (216, 'Turkmenistan', 'TM', 'TKM', 1);
INSERT INTO countries VALUES (217, 'Turks and Caicos Islands', 'TC', 'TCA', 1);
INSERT INTO countries VALUES (218, 'Tuvalu', 'TV', 'TUV', 1);
INSERT INTO countries VALUES (219, 'Uganda', 'UG', 'UGA', 1);
INSERT INTO countries VALUES (220, 'Ukraine', 'UA', 'UKR', 1);
INSERT INTO countries VALUES (221, 'United Arab Emirates', 'AE', 'ARE', 1);
INSERT INTO countries VALUES (222, 'United Kingdom', 'GB', 'GBR', 1);
INSERT INTO countries VALUES (223, 'United States', 'US', 'USA', 2);
INSERT INTO countries VALUES (224, 'United States Minor Outlying Islands', 'UM', 'UMI', 1);
INSERT INTO countries VALUES (225, 'Uruguay', 'UY', 'URY', 1);
INSERT INTO countries VALUES (226, 'Uzbekistan', 'UZ', 'UZB', 1);
INSERT INTO countries VALUES (227, 'Vanuatu', 'VU', 'VUT', 1);
INSERT INTO countries VALUES (228, 'Vatican City State (Holy See)', 'VA', 'VAT', 1);
INSERT INTO countries VALUES (229, 'Venezuela', 'VE', 'VEN', 1);
INSERT INTO countries VALUES (230, 'Viet Nam', 'VN', 'VNM', 1);
INSERT INTO countries VALUES (231, 'Virgin Islands (British)', 'VG', 'VGB', 1);
INSERT INTO countries VALUES (232, 'Virgin Islands (U.S.)', 'VI', 'VIR', 1);
INSERT INTO countries VALUES (233, 'Wallis and Futuna Islands', 'WF', 'WLF', 1);
INSERT INTO countries VALUES (234, 'Western Sahara', 'EH', 'ESH', 1);
INSERT INTO countries VALUES (235, 'Yemen', 'YE', 'YEM', 1);
INSERT INTO countries VALUES (236, 'Yugoslavia', 'YU', 'YUG', 1);
INSERT INTO countries VALUES (237, 'Zaire', 'ZR', 'ZAR', 1);
INSERT INTO countries VALUES (238, 'Zambia', 'ZM', 'ZMB', 1);
INSERT INTO countries VALUES (239, 'Zimbabwe', 'ZW', 'ZWE', 1);
SELECT setval('app.countries_countries_id_seq', 239, true);


