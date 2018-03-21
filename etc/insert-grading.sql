--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-06-24 12:32:42

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2125 (class 0 OID 62094)
-- Dependencies: 194 2126
-- Data for Name: grading; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO grading VALUES (1, 'A', 80, 100);
INSERT INTO grading VALUES (2, 'A-', 75, 79);
INSERT INTO grading VALUES (3, 'B+', 70, 74);
INSERT INTO grading VALUES (4, 'B', 65, 69);
INSERT INTO grading VALUES (5, 'B-', 60, 64);
INSERT INTO grading VALUES (6, 'C+', 55, 59);
INSERT INTO grading VALUES (7, 'C', 50, 54);
INSERT INTO grading VALUES (8, 'C-', 45, 49);
INSERT INTO grading VALUES (9, 'D+', 40, 44);
INSERT INTO grading VALUES (10, 'D', 35, 39);
INSERT INTO grading VALUES (11, 'D-', 30, 34);
INSERT INTO grading VALUES (12, 'E', 0, 29);


--
-- TOC entry 2130 (class 0 OID 0)
-- Dependencies: 193
-- Name: grading_grade_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('grading_grade_id_seq', 15, true);


-- Completed on 2016-06-24 12:32:43

--
-- PostgreSQL database dump complete
--

