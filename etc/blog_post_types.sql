--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-05-30 13:38:49

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = app, pg_catalog;


INSERT INTO blog_post_types VALUES (1, 'General');
INSERT INTO blog_post_types VALUES (2, 'Homework');

SELECT pg_catalog.setval('blog_post_types_post_type_id_seq', 2, true);


INSERT INTO blog_post_statuses VALUES (3, 'Deleted');
INSERT INTO blog_post_statuses VALUES (2, 'Draft');
INSERT INTO blog_post_statuses VALUES (1, 'Published');


SELECT pg_catalog.setval('blog_post_statuses_post_status_id_seq', 3, true);

