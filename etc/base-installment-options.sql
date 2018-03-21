--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-12 15:09:12

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2076 (class 0 OID 70599)
-- Dependencies: 239 2077
-- Data for Name: installment_options; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO installment_options VALUES (1, '50/50 Installment', true, 2, 30, 'days');
INSERT INTO installment_options VALUES (2, 'Per Term', true, 3, 4, 'month');
INSERT INTO installment_options VALUES (3, 'Per Month', true, 12, 1, 'month');


--
-- TOC entry 2081 (class 0 OID 0)
-- Dependencies: 238
-- Name: installment_options_installment_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('installment_options_installment_id_seq', 4, true);


-- Completed on 2016-05-12 15:09:12

--
-- PostgreSQL database dump complete
--

