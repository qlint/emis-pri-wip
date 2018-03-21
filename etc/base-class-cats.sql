--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-12 15:12:46

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;

--
-- TOC entry 2075 (class 0 OID 62022)
-- Dependencies: 184 2076
-- Data for Name: class_cats; Type: TABLE DATA; Schema: app; Owner: postgres
--

INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (1, 'Baby Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (2, 'Nursery Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (3, 'Preunit Class');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (4, 'Lower Primary');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (5, 'Mid Primary');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (6, 'Upper Primary');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (7, 'Test');
INSERT INTO class_cats (class_cat_id, class_cat_name) VALUES (8, 'Test2');


--
-- TOC entry 2080 (class 0 OID 0)
-- Dependencies: 183
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE SET; Schema: app; Owner: postgres
--

SELECT pg_catalog.setval('class_cats_class_cat_id_seq', 8, true);


-- Completed on 2016-05-12 15:12:46

--
-- PostgreSQL database dump complete
--

