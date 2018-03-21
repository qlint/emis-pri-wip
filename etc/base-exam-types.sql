--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-12 15:10:57

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2079 (class 0 OID 62600)
-- Dependencies: 222 2080
-- Data for Name: exam_types; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO exam_types VALUES (1, 'Opener', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 1);
INSERT INTO exam_types VALUES (2, 'Mid Term', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 2);
INSERT INTO exam_types VALUES (3, 'End Term', NULL, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 3);
INSERT INTO exam_types VALUES (5, '2 Week Assesment', 4, '2016-04-22 13:40:43.116', NULL, NULL, NULL, 2);


--
-- TOC entry 2084 (class 0 OID 0)
-- Dependencies: 221
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('exam_types_exam_type_id_seq', 9, true);


-- Completed on 2016-05-12 15:10:57

--
-- PostgreSQL database dump complete
--

