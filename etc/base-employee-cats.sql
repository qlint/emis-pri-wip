--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-12 15:12:05

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2075 (class 0 OID 62011)
-- Dependencies: 182 2076
-- Data for Name: employee_cats; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO employee_cats VALUES (1, 'Teaching');
INSERT INTO employee_cats VALUES (2, 'Non-teaching');


--
-- TOC entry 2080 (class 0 OID 0)
-- Dependencies: 181
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('employee_cats_emp_cat_id_seq', 2, true);


-- Completed on 2016-05-12 15:12:05

--
-- PostgreSQL database dump complete
--

