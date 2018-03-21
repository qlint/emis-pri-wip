--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-06-24 12:46:32

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2130 (class 0 OID 62198)
-- Dependencies: 202 2131
-- Data for Name: fee_items; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO fee_items VALUES (1, 'Caution Money', 4000, 'once', true, '2016-03-22 12:05:56.384', NULL, NULL, false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (6, 'Insurance', 1200, 'yearly', true, '2016-03-22 12:06:24.929', NULL, NULL, false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (8, 'P.T.A Fund', 500, 'yearly', true, '2016-03-22 12:06:36.202', NULL, NULL, false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (10, 'Project Fund', 1500, 'yearly', true, '2016-03-22 12:06:44.482', NULL, NULL, false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (12, 'Tuition Full Day', 27900, 'per term', true, '2016-03-22 12:06:55.922', NULL, '{1,2,3}', false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (13, 'Tuition', 33900, 'per term', true, '2016-03-22 12:07:03.913', NULL, '{4}', false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (15, 'Tuition', 36000, 'per term', true, '2016-03-22 15:33:02.963', NULL, '{5}', false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (16, 'Tuition', 38000, 'per term', true, '2016-03-22 15:33:14.753', NULL, '{6}', false, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (17, 'Tae-Kwondo', 3000, 'per term', true, '2016-03-22 15:34:25.768', NULL, NULL, true, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (18, 'Piano/Music', 4000, 'per term', true, '2016-03-22 15:34:37.777', NULL, NULL, true, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (19, 'French Classes', 4000, 'per term', true, '2016-03-22 15:34:55.849', NULL, NULL, true, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (20, 'Drama, public speaking and poetry', 3000, 'per term', true, '2016-03-22 15:35:11.241', NULL, NULL, true, false, false, NULL, NULL);
INSERT INTO fee_items VALUES (5, 'Interview', 1000, 'once', true, '2016-03-22 12:06:17.138', NULL, NULL, false, true, false, NULL, NULL);
INSERT INTO fee_items VALUES (4, 'Admission Fee', 3000, 'once', true, '2016-03-22 12:06:11.122', NULL, NULL, false, true, false, NULL, NULL);
INSERT INTO fee_items VALUES (7, 'School Diary', 250, 'yearly', true, '2016-03-22 12:06:29.386', NULL, NULL, false, false, true, NULL, NULL);
INSERT INTO fee_items VALUES (26, 'Report book', 500, 'once', true, '2016-03-22 15:39:12.026', NULL, NULL, false, false, true, NULL, NULL);
INSERT INTO fee_items VALUES (25, 'Textbooks', 8000, 'once', true, '2016-03-22 15:38:58.417', NULL, '{6}', false, false, true, NULL, NULL);
INSERT INTO fee_items VALUES (24, 'Textbooks', 6500, 'once', true, '2016-03-22 15:38:45.281', NULL, '{5}', false, false, true, NULL, NULL);
INSERT INTO fee_items VALUES (23, 'Textbooks', 5500, 'once', true, '2016-03-22 15:38:31.537', NULL, '{4}', false, false, true, NULL, NULL);
INSERT INTO fee_items VALUES (28, 'Transport', NULL, 'per term', true, '2016-04-20 16:53:20.672', NULL, NULL, true, true, true, '2016-04-21 11:16:37.388', 1);
INSERT INTO fee_items VALUES (11, 'Tuition Half Day', 21000, 'per term', true, '2016-03-22 12:06:50.339', NULL, '{1,2,3,7}', true, true, true, '2016-04-28 09:25:19.49', 1);
INSERT INTO fee_items VALUES (21, 'Swimming', 3000, 'per term', true, '2016-03-22 15:35:18.281', NULL, NULL, true, false, true, '2016-04-28 09:26:11.005', 1);
INSERT INTO fee_items VALUES (22, 'Textbooks', 3500, 'once', true, '2016-03-22 15:38:17.768', NULL, '{1,2,3}', false, false, false, NULL, NULL);


--
-- TOC entry 2135 (class 0 OID 0)
-- Dependencies: 201
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('fee_items_fee_item_id_seq', 33, true);


-- Completed on 2016-06-24 12:46:32

--
-- PostgreSQL database dump complete
--

