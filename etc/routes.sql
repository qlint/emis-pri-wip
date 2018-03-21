--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-06-24 12:47:08

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2129 (class 0 OID 62260)
-- Dependencies: 206 2130
-- Data for Name: transport_routes; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO transport_routes VALUES (6, 'Langata A (Upto HQ) - Both Ways', 9000, true, '2016-03-22 15:41:22.313', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (23, 'Langata A (Upto HQ) - One Way', 6000, true, '2016-04-20 20:27:29.483', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (7, 'Langata B (After HQ) - Both Ways', 10000, true, '2016-03-22 15:41:39.578', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (22, 'Langata B (After HQ) - One Way', 7000, true, '2016-04-20 20:27:11.532', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (3, 'Madaraka - Both Ways', 7500, true, '2016-03-22 15:40:45.178', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (26, 'Madaraka - One Way', 5000, true, '2016-04-20 20:29:49.419', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (12, 'Mombasa Road (Between Bellevue & Cabanas) - Both Ways', 12000, true, '2016-03-22 15:42:47.193', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (15, 'Mombasa Road (Between Bellevue & Cabanas) - One Way', 7500, true, '2016-04-20 20:24:28.435', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (2, 'Nairobi West - Both Ways', 7500, true, '2016-03-22 15:40:35.145', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (27, 'Nairobi West - One Way', 5000, true, '2016-04-20 20:30:06.643', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (5, 'Ngumo - Both Ways', 8500, true, '2016-03-22 15:41:05.921', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (24, 'Ngumo - One Way', 6000, true, '2016-04-20 20:27:48.052', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (4, 'Nyayo High-Rise - Both Ways', 7500, true, '2016-03-22 15:40:55.505', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (25, 'Nyayo High-Rise - One Way', 5000, true, '2016-04-20 20:29:32.587', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (8, 'Otiende - Both Ways', 11000, true, '2016-03-22 15:41:49.097', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (21, 'Otiende - One Way', 7500, true, '2016-04-20 20:26:51.467', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (11, 'South B - Both Ways', 10000, true, '2016-03-22 15:42:28.714', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (16, 'South B - One Way', 7000, true, '2016-04-20 20:25:07.147', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (9, 'South C 1 (Before Mugoya) - Both Ways', 8000, true, '2016-03-22 15:42:13.481', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (20, 'South C 1 (Before Mugoya) - One Way', 5500, true, '2016-04-20 20:25:52.012', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (10, 'South C 2 - Both Ways', 9000, true, '2016-03-22 15:42:20.569', NULL, '2016-04-21 11:16:37.224', 1);
INSERT INTO transport_routes VALUES (19, 'South C 2 - One Way', 6000, true, '2016-04-20 20:25:26.387', NULL, '2016-04-21 11:16:37.224', 1);


--
-- TOC entry 2134 (class 0 OID 0)
-- Dependencies: 205
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('transport_routes_transport_id_seq', 32, true);


-- Completed on 2016-06-24 12:47:08

--
-- PostgreSQL database dump complete
--

