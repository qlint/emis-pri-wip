--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-11-01 15:53:37

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 7 (class 2615 OID 62000)
-- Name: app; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA app;


ALTER SCHEMA app OWNER TO postgres;

--
-- TOC entry 274 (class 3079 OID 11638)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2461 (class 0 OID 0)
-- Dependencies: 274
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = app, pg_catalog;

--
-- TOC entry 286 (class 1255 OID 79027)
-- Dependencies: 7 817
-- Name: colpivot(character varying, character varying, character varying[], character varying[], character varying, character varying); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION colpivot(out_table character varying, in_query character varying, key_cols character varying[], class_cols character varying[], value_e character varying, col_order character varying) RETURNS void
    LANGUAGE plpgsql
    AS $$
    declare
        in_table varchar;
        col varchar;
        ali varchar;
        on_e varchar;
        i integer;
        rec record;
        query varchar;
        -- This is actually an array of arrays but postgres does not support an array of arrays type so we flatten it.
        -- We could theoretically use the matrix feature but it's extremly cancerogenous and we would have to involve
        -- custom aggrigates. For most intents and purposes postgres does not have a multi-dimensional array type.
        clsc_cols text[] := array[]::text[];
        n_clsc_cols integer;
        n_class_cols integer;
    begin
        in_table := quote_ident('__' || out_table || '_in');
        execute ('create temp table ' || in_table || ' on commit drop as ' || in_query);
        -- get ordered unique columns (column combinations)
        query := 'select array[';
        i := 0;
        foreach col in array class_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || 'quote_literal(' || quote_ident(col) || ')';
            i := i + 1;
        end loop;
        query := query || '] x from ' || in_table;
        for j in 1..2 loop
            if j = 1 then
                query := query || ' group by ';
            else
                query := query || ' order by ';
                if col_order is not null then
                    query := query || col_order || ' ';
                    exit;
                end if;
            end if;
            i := 0;
            foreach col in array class_cols loop
                if i > 0 then
                    query := query || ', ';
                end if;
                query := query || quote_ident(col);
                i := i + 1;
            end loop;
        end loop;
        -- raise notice '%', query;
        for rec in
            execute query
        loop
            clsc_cols := array_cat(clsc_cols, rec.x);
        end loop;
        n_class_cols := array_length(class_cols, 1);
        n_clsc_cols := array_length(clsc_cols, 1) / n_class_cols;
        -- build target query
        query := 'select ';
        i := 0;
        foreach col in array key_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || '_key.' || quote_ident(col) || ' ';
            i := i + 1;
        end loop;
        for j in 1..n_clsc_cols loop
            query := query || ', ';
            col := '';
            for k in 1..n_class_cols loop
                if k > 1 then
                    col := col || ', ';
                end if;
                col := col || clsc_cols[(j - 1) * n_class_cols + k];
            end loop;
            ali := '_clsc_' || j::text;
            query := query || '(' || replace(value_e, '#', ali) || ')' || ' as ' || quote_ident(col) || ' ';
        end loop;
        query := query || ' from (select distinct ';
        i := 0;
        foreach col in array key_cols loop
            if i > 0 then
                query := query || ', ';
            end if;
            query := query || quote_ident(col) || ' ';
            i := i + 1;
        end loop;
        query := query || ' from ' || in_table || ') _key ';
        for j in 1..n_clsc_cols loop
            ali := '_clsc_' || j::text;
            on_e := '';
            i := 0;
            foreach col in array key_cols loop
                if i > 0 then
                    on_e := on_e || ' and ';
                end if;
                on_e := on_e || ali || '.' || quote_ident(col) || ' = _key.' || quote_ident(col) || ' ';
                i := i + 1;
            end loop;
            for k in 1..n_class_cols loop
                on_e := on_e || ' and ';
                on_e := on_e || ali || '.' || quote_ident(class_cols[k]) || ' = ' || clsc_cols[(j - 1) * n_class_cols + k];
            end loop;
            query := query || 'left join ' || in_table || ' as ' || ali || ' on ' || on_e || ' ';
        end loop;
        -- raise notice '%', query;
        execute ('create temp table ' || quote_ident(out_table) || ' on commit drop as ' || query);
        -- cleanup temporary in_table before we return
        execute ('drop table ' || in_table)
        return;
    end;
$$;


ALTER FUNCTION app.colpivot(out_table character varying, in_query character varying, key_cols character varying[], class_cols character varying[], value_e character varying, col_order character varying) OWNER TO postgres;

--
-- TOC entry 287 (class 1255 OID 119485)
-- Dependencies: 7 817
-- Name: set_invoice_term(); Type: FUNCTION; Schema: app; Owner: postgres
--

CREATE FUNCTION set_invoice_term() RETURNS boolean
    LANGUAGE plpgsql
    AS $$
declare
	_result record;
begin

for _result in 
		select inv_id, (select term_id from app.terms where due_date between start_date and end_date) as term_id
		from app.invoices
	loop
		update app.invoices set term_id = _result.term_id where inv_id = _result.inv_id;
	end loop;
	return true;
end;
$$;


ALTER FUNCTION app.set_invoice_term() OWNER TO postgres;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 262 (class 1259 OID 100861)
-- Dependencies: 7
-- Name: blog_post_statuses; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_post_statuses (
    post_status_id integer NOT NULL,
    post_status character varying NOT NULL
);


ALTER TABLE app.blog_post_statuses OWNER TO postgres;

--
-- TOC entry 261 (class 1259 OID 100859)
-- Dependencies: 262 7
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_post_statuses_post_status_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_statuses_post_status_id_seq OWNER TO postgres;

--
-- TOC entry 2462 (class 0 OID 0)
-- Dependencies: 261
-- Name: blog_post_statuses_post_status_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_post_statuses_post_status_id_seq OWNED BY blog_post_statuses.post_status_id;


--
-- TOC entry 258 (class 1259 OID 91254)
-- Dependencies: 7
-- Name: blog_post_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_post_types (
    post_type_id integer NOT NULL,
    post_type character varying NOT NULL
);


ALTER TABLE app.blog_post_types OWNER TO postgres;

--
-- TOC entry 257 (class 1259 OID 91252)
-- Dependencies: 258 7
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_post_types_post_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_post_types_post_type_id_seq OWNER TO postgres;

--
-- TOC entry 2463 (class 0 OID 0)
-- Dependencies: 257
-- Name: blog_post_types_post_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_post_types_post_type_id_seq OWNED BY blog_post_types.post_type_id;


--
-- TOC entry 264 (class 1259 OID 100902)
-- Dependencies: 2281 7
-- Name: blog_posts; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blog_posts (
    post_id integer NOT NULL,
    blog_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    post_type_id integer,
    body text,
    title character varying NOT NULL,
    post_status_id integer NOT NULL,
    feature_image character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.blog_posts OWNER TO postgres;

--
-- TOC entry 263 (class 1259 OID 100900)
-- Dependencies: 7 264
-- Name: blog_posts_post_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blog_posts_post_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blog_posts_post_id_seq OWNER TO postgres;

--
-- TOC entry 2464 (class 0 OID 0)
-- Dependencies: 263
-- Name: blog_posts_post_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blog_posts_post_id_seq OWNED BY blog_posts.post_id;


--
-- TOC entry 256 (class 1259 OID 91178)
-- Dependencies: 7
-- Name: blogs; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE blogs (
    blog_id integer NOT NULL,
    teacher_id integer NOT NULL,
    class_id integer NOT NULL,
    blog_name character varying
);


ALTER TABLE app.blogs OWNER TO postgres;

--
-- TOC entry 255 (class 1259 OID 91176)
-- Dependencies: 256 7
-- Name: blogs_blog_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE blogs_blog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.blogs_blog_id_seq OWNER TO postgres;

--
-- TOC entry 2465 (class 0 OID 0)
-- Dependencies: 255
-- Name: blogs_blog_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE blogs_blog_id_seq OWNED BY blogs.blog_id;


--
-- TOC entry 190 (class 1259 OID 62022)
-- Dependencies: 2188 2189 7
-- Name: class_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_cats (
    class_cat_id integer NOT NULL,
    class_cat_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.class_cats OWNER TO postgres;

--
-- TOC entry 189 (class 1259 OID 62020)
-- Dependencies: 190 7
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_cats_class_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_cats_class_cat_id_seq OWNER TO postgres;

--
-- TOC entry 2466 (class 0 OID 0)
-- Dependencies: 189
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_cats_class_cat_id_seq OWNED BY class_cats.class_cat_id;


--
-- TOC entry 228 (class 1259 OID 62621)
-- Dependencies: 2250 2251 7
-- Name: class_subject_exams; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_subject_exams (
    class_sub_exam_id integer NOT NULL,
    class_subject_id integer NOT NULL,
    exam_type_id integer NOT NULL,
    grade_weight integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.class_subject_exams OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 62619)
-- Dependencies: 228 7
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_subject_exams_class_sub_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subject_exams_class_sub_exam_id_seq OWNER TO postgres;

--
-- TOC entry 2467 (class 0 OID 0)
-- Dependencies: 227
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subject_exams_class_sub_exam_id_seq OWNED BY class_subject_exams.class_sub_exam_id;


--
-- TOC entry 224 (class 1259 OID 62582)
-- Dependencies: 2245 2246 7
-- Name: class_subjects; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_subjects (
    class_subject_id integer NOT NULL,
    class_id integer NOT NULL,
    subject_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.class_subjects OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 62580)
-- Dependencies: 7 224
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE class_subjects_class_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.class_subjects_class_subject_id_seq OWNER TO postgres;

--
-- TOC entry 2468 (class 0 OID 0)
-- Dependencies: 223
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subjects_class_subject_id_seq OWNED BY class_subjects.class_subject_id;


--
-- TOC entry 194 (class 1259 OID 62054)
-- Dependencies: 2194 2195 7
-- Name: classes; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE classes (
    class_id integer NOT NULL,
    class_name character varying NOT NULL,
    class_cat_id integer NOT NULL,
    teacher_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer,
    report_card_type character varying
);


ALTER TABLE app.classes OWNER TO postgres;

--
-- TOC entry 193 (class 1259 OID 62052)
-- Dependencies: 194 7
-- Name: classes_class_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE classes_class_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.classes_class_id_seq OWNER TO postgres;

--
-- TOC entry 2469 (class 0 OID 0)
-- Dependencies: 193
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE classes_class_id_seq OWNED BY classes.class_id;


--
-- TOC entry 268 (class 1259 OID 117324)
-- Dependencies: 7
-- Name: communication_audience; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_audience (
    audience_id integer NOT NULL,
    audience character varying NOT NULL
);


ALTER TABLE app.communication_audience OWNER TO postgres;

--
-- TOC entry 267 (class 1259 OID 117322)
-- Dependencies: 268 7
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_audience_audience_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_audience_audience_id_seq OWNER TO postgres;

--
-- TOC entry 2470 (class 0 OID 0)
-- Dependencies: 267
-- Name: communication_audience_audience_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_audience_audience_id_seq OWNED BY communication_audience.audience_id;


--
-- TOC entry 260 (class 1259 OID 91406)
-- Dependencies: 7
-- Name: communication_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communication_types (
    com_type_id integer NOT NULL,
    com_type character varying NOT NULL
);


ALTER TABLE app.communication_types OWNER TO postgres;

--
-- TOC entry 259 (class 1259 OID 91404)
-- Dependencies: 7 260
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communication_types_com_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communication_types_com_type_id_seq OWNER TO postgres;

--
-- TOC entry 2471 (class 0 OID 0)
-- Dependencies: 259
-- Name: communication_types_com_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communication_types_com_type_id_seq OWNED BY communication_types.com_type_id;


--
-- TOC entry 270 (class 1259 OID 117592)
-- Dependencies: 2286 2287 7
-- Name: communications; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE communications (
    com_id integer NOT NULL,
    com_date date,
    audience_id integer NOT NULL,
    com_type_id integer NOT NULL,
    post_status_id integer,
    subject character varying,
    message character varying NOT NULL,
    attachment character varying,
    message_from integer NOT NULL,
    student_id integer,
    guardian_id integer,
    class_id integer,
    send_as_email boolean,
    send_as_sms boolean,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    reply_to character varying,
    sent boolean DEFAULT false NOT NULL,
    sent_date timestamp without time zone,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.communications OWNER TO postgres;

--
-- TOC entry 269 (class 1259 OID 117590)
-- Dependencies: 270 7
-- Name: communications_com_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE communications_com_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.communications_com_id_seq OWNER TO postgres;

--
-- TOC entry 2472 (class 0 OID 0)
-- Dependencies: 269
-- Name: communications_com_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE communications_com_id_seq OWNED BY communications.com_id;


--
-- TOC entry 216 (class 1259 OID 62451)
-- Dependencies: 7
-- Name: countries; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE countries (
    countries_id integer NOT NULL,
    countries_name character varying(255) NOT NULL,
    countries_iso_code_2 character(2) NOT NULL,
    countries_iso_code_3 character(3) NOT NULL,
    address_format_id integer NOT NULL
);


ALTER TABLE app.countries OWNER TO postgres;

--
-- TOC entry 215 (class 1259 OID 62449)
-- Dependencies: 7 216
-- Name: countries_countries_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE countries_countries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.countries_countries_id_seq OWNER TO postgres;

--
-- TOC entry 2473 (class 0 OID 0)
-- Dependencies: 215
-- Name: countries_countries_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE countries_countries_id_seq OWNED BY countries.countries_id;


--
-- TOC entry 273 (class 1259 OID 118740)
-- Dependencies: 2288 2290 7
-- Name: credits; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE credits (
    credit_id integer NOT NULL,
    student_id integer NOT NULL,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    amount_applied numeric DEFAULT 0 NOT NULL,
    payment_id integer
);


ALTER TABLE app.credits OWNER TO postgres;

--
-- TOC entry 272 (class 1259 OID 118738)
-- Dependencies: 7 273
-- Name: credits_credit_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE credits_credit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.credits_credit_id_seq OWNER TO postgres;

--
-- TOC entry 2474 (class 0 OID 0)
-- Dependencies: 272
-- Name: credits_credit_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE credits_credit_id_seq OWNED BY credits.credit_id;


--
-- TOC entry 200 (class 1259 OID 62106)
-- Dependencies: 2202 7
-- Name: terms; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE terms (
    term_id integer NOT NULL,
    term_name character varying NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    term_number integer
);


ALTER TABLE app.terms OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 70208)
-- Dependencies: 2177 7
-- Name: current_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW current_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date > terms.start_date) ORDER BY terms.start_date DESC LIMIT 1;


ALTER TABLE app.current_term OWNER TO postgres;

--
-- TOC entry 192 (class 1259 OID 62039)
-- Dependencies: 2191 2192 7
-- Name: departments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE departments (
    dept_id integer NOT NULL,
    dept_name character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    category character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.departments OWNER TO postgres;

--
-- TOC entry 191 (class 1259 OID 62037)
-- Dependencies: 7 192
-- Name: departments_dept_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE departments_dept_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.departments_dept_id_seq OWNER TO postgres;

--
-- TOC entry 2475 (class 0 OID 0)
-- Dependencies: 191
-- Name: departments_dept_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE departments_dept_id_seq OWNED BY departments.dept_id;


--
-- TOC entry 188 (class 1259 OID 62011)
-- Dependencies: 2185 2186 7
-- Name: employee_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE employee_cats (
    emp_cat_id integer NOT NULL,
    emp_cat_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.employee_cats OWNER TO postgres;

--
-- TOC entry 187 (class 1259 OID 62009)
-- Dependencies: 7 188
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE employee_cats_emp_cat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employee_cats_emp_cat_id_seq OWNER TO postgres;

--
-- TOC entry 2476 (class 0 OID 0)
-- Dependencies: 187
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employee_cats_emp_cat_id_seq OWNED BY employee_cats.emp_cat_id;


--
-- TOC entry 202 (class 1259 OID 62120)
-- Dependencies: 2204 2205 7
-- Name: employees; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE employees (
    emp_id integer NOT NULL,
    emp_cat_id integer NOT NULL,
    dept_id integer NOT NULL,
    emp_number character varying,
    id_number character varying,
    gender character(1),
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    initials character varying,
    country character varying,
    active boolean DEFAULT true NOT NULL,
    telephone character varying,
    email character varying,
    joined_date date,
    job_title character varying,
    qualifications character varying,
    experience character varying,
    additional_info character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    emp_image character varying,
    next_of_kin_name character varying,
    next_of_kin_telephone character varying,
    next_of_kin_email character varying,
    modified_date timestamp without time zone,
    modified_by integer,
    login_id integer,
    dob character varying
);


ALTER TABLE app.employees OWNER TO postgres;

--
-- TOC entry 201 (class 1259 OID 62118)
-- Dependencies: 202 7
-- Name: employees_emp_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE employees_emp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.employees_emp_id_seq OWNER TO postgres;

--
-- TOC entry 2477 (class 0 OID 0)
-- Dependencies: 201
-- Name: employees_emp_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employees_emp_id_seq OWNED BY employees.emp_id;


--
-- TOC entry 230 (class 1259 OID 62641)
-- Dependencies: 2253 7
-- Name: exam_marks; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE exam_marks (
    exam_id integer NOT NULL,
    student_id integer NOT NULL,
    class_sub_exam_id integer NOT NULL,
    term_id integer NOT NULL,
    mark integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.exam_marks OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 62639)
-- Dependencies: 7 230
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE exam_marks_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_marks_exam_id_seq OWNER TO postgres;

--
-- TOC entry 2478 (class 0 OID 0)
-- Dependencies: 229
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_marks_exam_id_seq OWNED BY exam_marks.exam_id;


--
-- TOC entry 226 (class 1259 OID 62600)
-- Dependencies: 2248 7
-- Name: exam_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE exam_types (
    exam_type_id integer NOT NULL,
    exam_type character varying NOT NULL,
    class_cat_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer
);


ALTER TABLE app.exam_types OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 62598)
-- Dependencies: 7 226
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE exam_types_exam_type_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.exam_types_exam_type_id_seq OWNER TO postgres;

--
-- TOC entry 2479 (class 0 OID 0)
-- Dependencies: 225
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_types_exam_type_id_seq OWNED BY exam_types.exam_type_id;


--
-- TOC entry 206 (class 1259 OID 62198)
-- Dependencies: 2219 2220 2221 2222 2223 7
-- Name: fee_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE fee_items (
    fee_item_id integer NOT NULL,
    fee_item character varying NOT NULL,
    default_amount double precision,
    frequency character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    class_cats_restriction integer[],
    optional boolean DEFAULT false,
    new_student_only boolean DEFAULT false,
    replaceable boolean DEFAULT false NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.fee_items OWNER TO postgres;

--
-- TOC entry 205 (class 1259 OID 62196)
-- Dependencies: 7 206
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE fee_items_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.fee_items_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 2480 (class 0 OID 0)
-- Dependencies: 205
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE fee_items_fee_item_id_seq OWNED BY fee_items.fee_item_id;


--
-- TOC entry 198 (class 1259 OID 62094)
-- Dependencies: 7
-- Name: grading; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE grading (
    grade_id integer NOT NULL,
    grade character varying NOT NULL,
    min_mark integer NOT NULL,
    max_mark integer NOT NULL
);


ALTER TABLE app.grading OWNER TO postgres;

--
-- TOC entry 197 (class 1259 OID 62092)
-- Dependencies: 198 7
-- Name: grading_grade_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE grading_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.grading_grade_id_seq OWNER TO postgres;

--
-- TOC entry 2481 (class 0 OID 0)
-- Dependencies: 197
-- Name: grading_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE grading_grade_id_seq OWNED BY grading.grade_id;


--
-- TOC entry 222 (class 1259 OID 62537)
-- Dependencies: 2242 2243 7
-- Name: guardians; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE guardians (
    guardian_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    id_number character varying NOT NULL,
    telephone character varying,
    email character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    occupation character varying,
    address character varying,
    title character varying,
    marital_status character varying,
    work_email character varying,
    employer character varying,
    employer_address character varying,
    work_phone character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.guardians OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 62535)
-- Dependencies: 222 7
-- Name: guardians_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE guardians_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.guardians_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 2482 (class 0 OID 0)
-- Dependencies: 221
-- Name: guardians_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE guardians_guardian_id_seq OWNED BY guardians.guardian_id;


--
-- TOC entry 266 (class 1259 OID 117259)
-- Dependencies: 2283 7
-- Name: homework; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE homework (
    homework_id integer NOT NULL,
    class_subject_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    due_date timestamp without time zone,
    assigned_date timestamp without time zone,
    body text,
    title character varying NOT NULL,
    post_status_id integer NOT NULL,
    attachment character varying,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.homework OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 117257)
-- Dependencies: 266 7
-- Name: homework_homework_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE homework_homework_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.homework_homework_id_seq OWNER TO postgres;

--
-- TOC entry 2483 (class 0 OID 0)
-- Dependencies: 265
-- Name: homework_homework_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE homework_homework_id_seq OWNED BY homework.homework_id;


--
-- TOC entry 243 (class 1259 OID 70599)
-- Dependencies: 2266 7
-- Name: installment_options; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE installment_options (
    installment_id integer NOT NULL,
    payment_plan_name character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    num_payments integer,
    payment_interval integer,
    payment_interval2 character varying
);


ALTER TABLE app.installment_options OWNER TO postgres;

--
-- TOC entry 2484 (class 0 OID 0)
-- Dependencies: 243
-- Name: COLUMN installment_options.payment_interval; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN installment_options.payment_interval IS 'number of days';


--
-- TOC entry 242 (class 1259 OID 70597)
-- Dependencies: 7 243
-- Name: installment_options_installment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE installment_options_installment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.installment_options_installment_id_seq OWNER TO postgres;

--
-- TOC entry 2485 (class 0 OID 0)
-- Dependencies: 242
-- Name: installment_options_installment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE installment_options_installment_id_seq OWNED BY installment_options.installment_id;


--
-- TOC entry 240 (class 1259 OID 70588)
-- Dependencies: 7
-- Name: invoice_balances; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_balances (
    student_id integer,
    inv_id integer,
    inv_date date,
    total_due numeric,
    total_paid numeric,
    balance numeric,
    due_date date,
    past_due boolean,
    canceled boolean
);


ALTER TABLE app.invoice_balances OWNER TO postgres;

--
-- TOC entry 271 (class 1259 OID 118104)
-- Dependencies: 7
-- Name: invoice_balances2; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_balances2 (
    student_id integer,
    inv_id integer,
    inv_date date,
    total_due numeric,
    total_paid numeric,
    balance numeric,
    due_date date,
    past_due boolean,
    canceled boolean,
    term_id integer
);


ALTER TABLE app.invoice_balances2 OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 70295)
-- Dependencies: 2259 7
-- Name: invoice_line_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoice_line_items (
    inv_item_id integer NOT NULL,
    inv_id integer NOT NULL,
    student_fee_item_id integer NOT NULL,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.invoice_line_items OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 70293)
-- Dependencies: 7 235
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE invoice_line_items_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoice_line_items_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 2486 (class 0 OID 0)
-- Dependencies: 234
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoice_line_items_inv_item_id_seq OWNED BY invoice_line_items.inv_item_id;


--
-- TOC entry 233 (class 1259 OID 70280)
-- Dependencies: 2255 2256 2257 7
-- Name: invoices; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE invoices (
    inv_id integer NOT NULL,
    student_id integer NOT NULL,
    inv_date date NOT NULL,
    total_amount numeric NOT NULL,
    due_date date NOT NULL,
    paid_in_full boolean DEFAULT false NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    canceled boolean DEFAULT false NOT NULL,
    term_id integer
);


ALTER TABLE app.invoices OWNER TO postgres;

--
-- TOC entry 232 (class 1259 OID 70278)
-- Dependencies: 233 7
-- Name: invoices_inv_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE invoices_inv_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.invoices_inv_id_seq OWNER TO postgres;

--
-- TOC entry 2487 (class 0 OID 0)
-- Dependencies: 232
-- Name: invoices_inv_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoices_inv_id_seq OWNED BY invoices.inv_id;


--
-- TOC entry 220 (class 1259 OID 62499)
-- Dependencies: 7
-- Name: medical_conditions; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE medical_conditions (
    condition_id integer NOT NULL,
    illness_condition character varying NOT NULL
);


ALTER TABLE app.medical_conditions OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 62497)
-- Dependencies: 220 7
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE medical_conditions_condition_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.medical_conditions_condition_id_seq OWNER TO postgres;

--
-- TOC entry 2488 (class 0 OID 0)
-- Dependencies: 219
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE medical_conditions_condition_id_seq OWNED BY medical_conditions.condition_id;


--
-- TOC entry 241 (class 1259 OID 70593)
-- Dependencies: 2179 7
-- Name: next_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW next_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date LIMIT 1;


ALTER TABLE app.next_term OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 70458)
-- Dependencies: 2264 7
-- Name: payment_inv_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payment_inv_items (
    payment_inv_item_id integer NOT NULL,
    payment_id integer NOT NULL,
    inv_id integer NOT NULL,
    inv_item_id integer,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.payment_inv_items OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 70456)
-- Dependencies: 7 239
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payment_inv_items_payment_inv_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_inv_items_payment_inv_item_id_seq OWNER TO postgres;

--
-- TOC entry 2489 (class 0 OID 0)
-- Dependencies: 238
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_inv_items_payment_inv_item_id_seq OWNED BY payment_inv_items.payment_inv_item_id;


--
-- TOC entry 245 (class 1259 OID 70672)
-- Dependencies: 2268 7
-- Name: payment_replacement_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payment_replacement_items (
    payment_replace_item_id integer NOT NULL,
    payment_id integer NOT NULL,
    student_fee_item_id integer,
    amount numeric NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.payment_replacement_items OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 70670)
-- Dependencies: 245 7
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payment_replacement_items_payment_replace_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payment_replacement_items_payment_replace_item_id_seq OWNER TO postgres;

--
-- TOC entry 2490 (class 0 OID 0)
-- Dependencies: 244
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_replacement_items_payment_replace_item_id_seq OWNED BY payment_replacement_items.payment_replace_item_id;


--
-- TOC entry 212 (class 1259 OID 62319)
-- Dependencies: 2231 2232 2233 7
-- Name: payments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE payments (
    payment_id integer NOT NULL,
    student_id integer NOT NULL,
    payment_date date NOT NULL,
    amount numeric NOT NULL,
    payment_method character varying NOT NULL,
    slip_cheque_no character varying,
    replacement_payment boolean DEFAULT false NOT NULL,
    reversed boolean DEFAULT false NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    reversed_date timestamp without time zone,
    reversed_by integer,
    inv_id integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.payments OWNER TO postgres;

--
-- TOC entry 2491 (class 0 OID 0)
-- Dependencies: 212
-- Name: COLUMN payments.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN payments.payment_method IS 'Cash or Cheque';


--
-- TOC entry 211 (class 1259 OID 62317)
-- Dependencies: 7 212
-- Name: payments_payment_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE payments_payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.payments_payment_id_seq OWNER TO postgres;

--
-- TOC entry 2492 (class 0 OID 0)
-- Dependencies: 211
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payments_payment_id_seq OWNED BY payments.payment_id;


--
-- TOC entry 250 (class 1259 OID 82444)
-- Dependencies: 2182 7
-- Name: previous_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW previous_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE (terms.start_date < (SELECT current_term.start_date FROM current_term)) ORDER BY terms.start_date DESC LIMIT 1;


ALTER TABLE app.previous_term OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 82321)
-- Dependencies: 2270 2271 7
-- Name: report_cards; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE report_cards (
    report_card_id integer NOT NULL,
    student_id integer,
    term_id integer,
    class_id integer,
    report_data text,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer,
    report_card_type character varying NOT NULL,
    teacher_id integer,
    published boolean DEFAULT false NOT NULL
);


ALTER TABLE app.report_cards OWNER TO postgres;

--
-- TOC entry 248 (class 1259 OID 82319)
-- Dependencies: 249 7
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE report_cards_report_card_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.report_cards_report_card_id_seq OWNER TO postgres;

--
-- TOC entry 2493 (class 0 OID 0)
-- Dependencies: 248
-- Name: report_cards_report_card_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE report_cards_report_card_id_seq OWNED BY report_cards.report_card_id;


--
-- TOC entry 186 (class 1259 OID 62001)
-- Dependencies: 7
-- Name: settings; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE settings (
    name character varying NOT NULL,
    value character varying NOT NULL
);


ALTER TABLE app.settings OWNER TO postgres;

--
-- TOC entry 208 (class 1259 OID 62215)
-- Dependencies: 2225 2226 7
-- Name: student_class_history; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_class_history (
    class_history_id integer NOT NULL,
    student_id integer NOT NULL,
    class_id integer NOT NULL,
    start_date timestamp without time zone DEFAULT now() NOT NULL,
    end_date date,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer
);


ALTER TABLE app.student_class_history OWNER TO postgres;

--
-- TOC entry 207 (class 1259 OID 62213)
-- Dependencies: 208 7
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_class_history_class_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_class_history_class_history_id_seq OWNER TO postgres;

--
-- TOC entry 2494 (class 0 OID 0)
-- Dependencies: 207
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_class_history_class_history_id_seq OWNED BY student_class_history.class_history_id;


--
-- TOC entry 204 (class 1259 OID 62166)
-- Dependencies: 2207 2208 2209 2210 2211 2212 2213 2214 2215 2216 2217 7
-- Name: students; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE students (
    student_id integer NOT NULL,
    admission_number character varying,
    gender character(1),
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    student_category character varying DEFAULT 'Regular'::character varying NOT NULL,
    nationality character varying,
    student_image character varying,
    active boolean DEFAULT true NOT NULL,
    current_class integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    payment_method character varying DEFAULT 'Annually'::character varying NOT NULL,
    admission_date date,
    marial_status_parents character varying,
    adopted boolean DEFAULT false NOT NULL,
    adopted_age character varying,
    marital_separation_age character varying,
    adoption_aware boolean DEFAULT false NOT NULL,
    medical_conditions boolean DEFAULT false NOT NULL,
    hospitalized boolean DEFAULT false NOT NULL,
    current_medical_treatment boolean DEFAULT false NOT NULL,
    hospitalized_description character varying,
    current_medical_treatment_description character varying,
    comments character varying,
    other_medical_conditions boolean DEFAULT false NOT NULL,
    other_medical_conditions_description character varying,
    emergency_name character varying,
    emergency_relationship character varying,
    emergency_telephone character varying,
    dob character varying,
    pick_up_drop_off_individual character varying,
    modified_date timestamp without time zone,
    modified_by integer,
    installment_option_id integer,
    new_student boolean DEFAULT false NOT NULL
);


ALTER TABLE app.students OWNER TO postgres;

--
-- TOC entry 196 (class 1259 OID 62074)
-- Dependencies: 2197 2198 2199 7
-- Name: subjects; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE subjects (
    subject_id integer NOT NULL,
    subject_name character varying NOT NULL,
    class_cat_id integer NOT NULL,
    teacher_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    sort_order integer,
    parent_subject_id integer,
    use_for_grading boolean DEFAULT true NOT NULL
);


ALTER TABLE app.subjects OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 79248)
-- Dependencies: 2181 7
-- Name: student_exam_marks; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW student_exam_marks AS
    SELECT students.student_id, (((((students.first_name)::text || ' '::text) || (COALESCE(students.middle_name, ''::character varying))::text) || ' '::text) || (students.last_name)::text) AS student_name, exam_marks.term_id, class_subjects.class_id, class_subject_exams.exam_type_id, exam_types.exam_type, subjects.subject_name, exam_marks.mark, class_subject_exams.class_sub_exam_id, class_subject_exams.grade_weight FROM ((((((class_subjects JOIN class_subject_exams USING (class_subject_id)) JOIN exam_types USING (exam_type_id)) JOIN subjects USING (subject_id)) JOIN classes USING (class_id)) JOIN students ON ((classes.class_id = students.current_class))) LEFT JOIN exam_marks ON (((students.student_id = exam_marks.student_id) AND (class_subject_exams.class_sub_exam_id = exam_marks.class_sub_exam_id))));


ALTER TABLE app.student_exam_marks OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 70423)
-- Dependencies: 2261 2262 7
-- Name: student_fee_items; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_fee_items (
    student_fee_item_id integer NOT NULL,
    student_id integer NOT NULL,
    fee_item_id integer NOT NULL,
    amount numeric NOT NULL,
    payment_method character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.student_fee_items OWNER TO postgres;

--
-- TOC entry 2495 (class 0 OID 0)
-- Dependencies: 237
-- Name: COLUMN student_fee_items.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN student_fee_items.payment_method IS 'This is an option from the Payment Options setting';


--
-- TOC entry 236 (class 1259 OID 70421)
-- Dependencies: 237 7
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_fee_items_student_fee_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_fee_items_student_fee_item_id_seq OWNER TO postgres;

--
-- TOC entry 2496 (class 0 OID 0)
-- Dependencies: 236
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_fee_items_student_fee_item_id_seq OWNED BY student_fee_items.student_fee_item_id;


--
-- TOC entry 254 (class 1259 OID 91037)
-- Dependencies: 2274 2275 7
-- Name: student_guardians; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_guardians (
    student_guardian_id integer NOT NULL,
    student_id integer NOT NULL,
    guardian_id integer NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    modified_date timestamp without time zone,
    modified_by integer,
    relationship character varying
);


ALTER TABLE app.student_guardians OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 91035)
-- Dependencies: 7 254
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_guardians_student_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_guardians_student_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 2497 (class 0 OID 0)
-- Dependencies: 253
-- Name: student_guardians_student_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_guardians_student_guardian_id_seq OWNED BY student_guardians.student_guardian_id;


--
-- TOC entry 218 (class 1259 OID 62483)
-- Dependencies: 2239 7
-- Name: student_medical_history; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_medical_history (
    medical_id integer NOT NULL,
    student_id integer,
    illness_condition character varying,
    age character varying,
    comments character varying,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.student_medical_history OWNER TO postgres;

--
-- TOC entry 217 (class 1259 OID 62481)
-- Dependencies: 7 218
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_medical_history_medical_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_medical_history_medical_id_seq OWNER TO postgres;

--
-- TOC entry 2498 (class 0 OID 0)
-- Dependencies: 217
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_medical_history_medical_id_seq OWNED BY student_medical_history.medical_id;


--
-- TOC entry 203 (class 1259 OID 62164)
-- Dependencies: 7 204
-- Name: students_student_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE students_student_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.students_student_id_seq OWNER TO postgres;

--
-- TOC entry 2499 (class 0 OID 0)
-- Dependencies: 203
-- Name: students_student_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE students_student_id_seq OWNED BY students.student_id;


--
-- TOC entry 195 (class 1259 OID 62072)
-- Dependencies: 196 7
-- Name: subjects_subject_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE subjects_subject_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.subjects_subject_id_seq OWNER TO postgres;

--
-- TOC entry 2500 (class 0 OID 0)
-- Dependencies: 195
-- Name: subjects_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE subjects_subject_id_seq OWNED BY subjects.subject_id;


--
-- TOC entry 246 (class 1259 OID 70800)
-- Dependencies: 2180 7
-- Name: term_after_next; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW term_after_next AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date OFFSET 1 LIMIT 1;


ALTER TABLE app.term_after_next OWNER TO postgres;

--
-- TOC entry 199 (class 1259 OID 62104)
-- Dependencies: 200 7
-- Name: terms_term_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE terms_term_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.terms_term_id_seq OWNER TO postgres;

--
-- TOC entry 2501 (class 0 OID 0)
-- Dependencies: 199
-- Name: terms_term_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE terms_term_id_seq OWNED BY terms.term_id;


--
-- TOC entry 210 (class 1259 OID 62260)
-- Dependencies: 2228 2229 7
-- Name: transport_routes; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE transport_routes (
    transport_id integer NOT NULL,
    route character varying NOT NULL,
    amount numeric NOT NULL,
    active boolean DEFAULT true NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.transport_routes OWNER TO postgres;

--
-- TOC entry 209 (class 1259 OID 62258)
-- Dependencies: 210 7
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE transport_routes_transport_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.transport_routes_transport_id_seq OWNER TO postgres;

--
-- TOC entry 2502 (class 0 OID 0)
-- Dependencies: 209
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE transport_routes_transport_id_seq OWNED BY transport_routes.transport_id;


--
-- TOC entry 252 (class 1259 OID 90183)
-- Dependencies: 7
-- Name: user_permissions; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE user_permissions (
    perm_id integer NOT NULL,
    user_type character varying NOT NULL,
    permissions text NOT NULL
);


ALTER TABLE app.user_permissions OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 90181)
-- Dependencies: 7 252
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE user_permissions_perm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_permissions_perm_id_seq OWNER TO postgres;

--
-- TOC entry 2503 (class 0 OID 0)
-- Dependencies: 251
-- Name: user_permissions_perm_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE user_permissions_perm_id_seq OWNED BY user_permissions.perm_id;


--
-- TOC entry 214 (class 1259 OID 62437)
-- Dependencies: 2235 2236 7
-- Name: users; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE users (
    user_id integer NOT NULL,
    username character varying NOT NULL,
    password character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying,
    last_name character varying NOT NULL,
    email character varying,
    user_type character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.users OWNER TO postgres;

--
-- TOC entry 213 (class 1259 OID 62435)
-- Dependencies: 214 7
-- Name: user_user_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE user_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.user_user_id_seq OWNER TO postgres;

--
-- TOC entry 2504 (class 0 OID 0)
-- Dependencies: 213
-- Name: user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE user_user_id_seq OWNED BY users.user_id;


--
-- TOC entry 2279 (class 2604 OID 100864)
-- Dependencies: 261 262 262
-- Name: post_status_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_post_statuses ALTER COLUMN post_status_id SET DEFAULT nextval('blog_post_statuses_post_status_id_seq'::regclass);


--
-- TOC entry 2277 (class 2604 OID 91257)
-- Dependencies: 258 257 258
-- Name: post_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_post_types ALTER COLUMN post_type_id SET DEFAULT nextval('blog_post_types_post_type_id_seq'::regclass);


--
-- TOC entry 2280 (class 2604 OID 100905)
-- Dependencies: 264 263 264
-- Name: post_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts ALTER COLUMN post_id SET DEFAULT nextval('blog_posts_post_id_seq'::regclass);


--
-- TOC entry 2276 (class 2604 OID 91181)
-- Dependencies: 256 255 256
-- Name: blog_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs ALTER COLUMN blog_id SET DEFAULT nextval('blogs_blog_id_seq'::regclass);


--
-- TOC entry 2187 (class 2604 OID 62025)
-- Dependencies: 190 189 190
-- Name: class_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_cats ALTER COLUMN class_cat_id SET DEFAULT nextval('class_cats_class_cat_id_seq'::regclass);


--
-- TOC entry 2249 (class 2604 OID 62624)
-- Dependencies: 228 227 228
-- Name: class_sub_exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams ALTER COLUMN class_sub_exam_id SET DEFAULT nextval('class_subject_exams_class_sub_exam_id_seq'::regclass);


--
-- TOC entry 2244 (class 2604 OID 62585)
-- Dependencies: 223 224 224
-- Name: class_subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects ALTER COLUMN class_subject_id SET DEFAULT nextval('class_subjects_class_subject_id_seq'::regclass);


--
-- TOC entry 2193 (class 2604 OID 62057)
-- Dependencies: 193 194 194
-- Name: class_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes ALTER COLUMN class_id SET DEFAULT nextval('classes_class_id_seq'::regclass);


--
-- TOC entry 2284 (class 2604 OID 117327)
-- Dependencies: 268 267 268
-- Name: audience_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_audience ALTER COLUMN audience_id SET DEFAULT nextval('communication_audience_audience_id_seq'::regclass);


--
-- TOC entry 2278 (class 2604 OID 91409)
-- Dependencies: 259 260 260
-- Name: com_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communication_types ALTER COLUMN com_type_id SET DEFAULT nextval('communication_types_com_type_id_seq'::regclass);


--
-- TOC entry 2285 (class 2604 OID 117595)
-- Dependencies: 270 269 270
-- Name: com_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications ALTER COLUMN com_id SET DEFAULT nextval('communications_com_id_seq'::regclass);


--
-- TOC entry 2237 (class 2604 OID 62454)
-- Dependencies: 216 215 216
-- Name: countries_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY countries ALTER COLUMN countries_id SET DEFAULT nextval('countries_countries_id_seq'::regclass);


--
-- TOC entry 2289 (class 2604 OID 118743)
-- Dependencies: 273 272 273
-- Name: credit_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits ALTER COLUMN credit_id SET DEFAULT nextval('credits_credit_id_seq'::regclass);


--
-- TOC entry 2190 (class 2604 OID 62042)
-- Dependencies: 192 191 192
-- Name: dept_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY departments ALTER COLUMN dept_id SET DEFAULT nextval('departments_dept_id_seq'::regclass);


--
-- TOC entry 2184 (class 2604 OID 62014)
-- Dependencies: 187 188 188
-- Name: emp_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employee_cats ALTER COLUMN emp_cat_id SET DEFAULT nextval('employee_cats_emp_cat_id_seq'::regclass);


--
-- TOC entry 2203 (class 2604 OID 62123)
-- Dependencies: 202 201 202
-- Name: emp_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees ALTER COLUMN emp_id SET DEFAULT nextval('employees_emp_id_seq'::regclass);


--
-- TOC entry 2252 (class 2604 OID 62644)
-- Dependencies: 229 230 230
-- Name: exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks ALTER COLUMN exam_id SET DEFAULT nextval('exam_marks_exam_id_seq'::regclass);


--
-- TOC entry 2247 (class 2604 OID 62603)
-- Dependencies: 225 226 226
-- Name: exam_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types ALTER COLUMN exam_type_id SET DEFAULT nextval('exam_types_exam_type_id_seq'::regclass);


--
-- TOC entry 2218 (class 2604 OID 62201)
-- Dependencies: 206 205 206
-- Name: fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY fee_items ALTER COLUMN fee_item_id SET DEFAULT nextval('fee_items_fee_item_id_seq'::regclass);


--
-- TOC entry 2200 (class 2604 OID 62097)
-- Dependencies: 197 198 198
-- Name: grade_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY grading ALTER COLUMN grade_id SET DEFAULT nextval('grading_grade_id_seq'::regclass);


--
-- TOC entry 2241 (class 2604 OID 62540)
-- Dependencies: 222 221 222
-- Name: guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY guardians ALTER COLUMN guardian_id SET DEFAULT nextval('guardians_guardian_id_seq'::regclass);


--
-- TOC entry 2282 (class 2604 OID 117262)
-- Dependencies: 265 266 266
-- Name: homework_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework ALTER COLUMN homework_id SET DEFAULT nextval('homework_homework_id_seq'::regclass);


--
-- TOC entry 2265 (class 2604 OID 70602)
-- Dependencies: 243 242 243
-- Name: installment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY installment_options ALTER COLUMN installment_id SET DEFAULT nextval('installment_options_installment_id_seq'::regclass);


--
-- TOC entry 2258 (class 2604 OID 70298)
-- Dependencies: 234 235 235
-- Name: inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items ALTER COLUMN inv_item_id SET DEFAULT nextval('invoice_line_items_inv_item_id_seq'::regclass);


--
-- TOC entry 2254 (class 2604 OID 70283)
-- Dependencies: 232 233 233
-- Name: inv_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices ALTER COLUMN inv_id SET DEFAULT nextval('invoices_inv_id_seq'::regclass);


--
-- TOC entry 2240 (class 2604 OID 62502)
-- Dependencies: 219 220 220
-- Name: condition_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY medical_conditions ALTER COLUMN condition_id SET DEFAULT nextval('medical_conditions_condition_id_seq'::regclass);


--
-- TOC entry 2263 (class 2604 OID 70461)
-- Dependencies: 239 238 239
-- Name: payment_inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items ALTER COLUMN payment_inv_item_id SET DEFAULT nextval('payment_inv_items_payment_inv_item_id_seq'::regclass);


--
-- TOC entry 2267 (class 2604 OID 70675)
-- Dependencies: 245 244 245
-- Name: payment_replace_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items ALTER COLUMN payment_replace_item_id SET DEFAULT nextval('payment_replacement_items_payment_replace_item_id_seq'::regclass);


--
-- TOC entry 2230 (class 2604 OID 62322)
-- Dependencies: 212 211 212
-- Name: payment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments ALTER COLUMN payment_id SET DEFAULT nextval('payments_payment_id_seq'::regclass);


--
-- TOC entry 2269 (class 2604 OID 82324)
-- Dependencies: 249 248 249
-- Name: report_card_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards ALTER COLUMN report_card_id SET DEFAULT nextval('report_cards_report_card_id_seq'::regclass);


--
-- TOC entry 2224 (class 2604 OID 62218)
-- Dependencies: 207 208 208
-- Name: class_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history ALTER COLUMN class_history_id SET DEFAULT nextval('student_class_history_class_history_id_seq'::regclass);


--
-- TOC entry 2260 (class 2604 OID 70426)
-- Dependencies: 237 236 237
-- Name: student_fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items ALTER COLUMN student_fee_item_id SET DEFAULT nextval('student_fee_items_student_fee_item_id_seq'::regclass);


--
-- TOC entry 2273 (class 2604 OID 91040)
-- Dependencies: 254 253 254
-- Name: student_guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians ALTER COLUMN student_guardian_id SET DEFAULT nextval('student_guardians_student_guardian_id_seq'::regclass);


--
-- TOC entry 2238 (class 2604 OID 62486)
-- Dependencies: 218 217 218
-- Name: medical_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history ALTER COLUMN medical_id SET DEFAULT nextval('student_medical_history_medical_id_seq'::regclass);


--
-- TOC entry 2206 (class 2604 OID 62169)
-- Dependencies: 204 203 204
-- Name: student_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students ALTER COLUMN student_id SET DEFAULT nextval('students_student_id_seq'::regclass);


--
-- TOC entry 2196 (class 2604 OID 62077)
-- Dependencies: 196 195 196
-- Name: subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects ALTER COLUMN subject_id SET DEFAULT nextval('subjects_subject_id_seq'::regclass);


--
-- TOC entry 2201 (class 2604 OID 62109)
-- Dependencies: 199 200 200
-- Name: term_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY terms ALTER COLUMN term_id SET DEFAULT nextval('terms_term_id_seq'::regclass);


--
-- TOC entry 2227 (class 2604 OID 62263)
-- Dependencies: 209 210 210
-- Name: transport_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY transport_routes ALTER COLUMN transport_id SET DEFAULT nextval('transport_routes_transport_id_seq'::regclass);


--
-- TOC entry 2272 (class 2604 OID 90186)
-- Dependencies: 251 252 252
-- Name: perm_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY user_permissions ALTER COLUMN perm_id SET DEFAULT nextval('user_permissions_perm_id_seq'::regclass);


--
-- TOC entry 2234 (class 2604 OID 62440)
-- Dependencies: 214 213 214
-- Name: user_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY users ALTER COLUMN user_id SET DEFAULT nextval('user_user_id_seq'::regclass);


--
-- TOC entry 2386 (class 2606 OID 91186)
-- Dependencies: 256 256 2455
-- Name: FK_blog_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_id" PRIMARY KEY (blog_id);


--
-- TOC entry 2396 (class 2606 OID 117268)
-- Dependencies: 266 266 2455
-- Name: FK_homework_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_id" PRIMARY KEY (homework_id);


--
-- TOC entry 2380 (class 2606 OID 82329)
-- Dependencies: 249 249 2455
-- Name: FK_report_card_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_card_id" PRIMARY KEY (report_card_id);


--
-- TOC entry 2398 (class 2606 OID 117332)
-- Dependencies: 268 268 2455
-- Name: PK_audience_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_audience
    ADD CONSTRAINT "PK_audience_id" PRIMARY KEY (audience_id);


--
-- TOC entry 2298 (class 2606 OID 62030)
-- Dependencies: 190 190 2455
-- Name: PK_class_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_cats
    ADD CONSTRAINT "PK_class_cat_id" PRIMARY KEY (class_cat_id);


--
-- TOC entry 2328 (class 2606 OID 62221)
-- Dependencies: 208 208 2455
-- Name: PK_class_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "PK_class_history_id" PRIMARY KEY (class_history_id);


--
-- TOC entry 2305 (class 2606 OID 62064)
-- Dependencies: 194 194 2455
-- Name: PK_class_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "PK_class_id" PRIMARY KEY (class_id);


--
-- TOC entry 2350 (class 2606 OID 62587)
-- Dependencies: 224 224 2455
-- Name: PK_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "PK_class_subject" PRIMARY KEY (class_subject_id);


--
-- TOC entry 2358 (class 2606 OID 62626)
-- Dependencies: 228 228 2455
-- Name: PK_class_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "PK_class_subject_exam" PRIMARY KEY (class_sub_exam_id);


--
-- TOC entry 2400 (class 2606 OID 117602)
-- Dependencies: 270 270 2455
-- Name: PK_com_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "PK_com_id" PRIMARY KEY (com_id);


--
-- TOC entry 2390 (class 2606 OID 91414)
-- Dependencies: 260 260 2455
-- Name: PK_com_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY communication_types
    ADD CONSTRAINT "PK_com_type_id" PRIMARY KEY (com_type_id);


--
-- TOC entry 2344 (class 2606 OID 62507)
-- Dependencies: 220 220 2455
-- Name: PK_condition_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY medical_conditions
    ADD CONSTRAINT "PK_condition_id" PRIMARY KEY (condition_id);


--
-- TOC entry 2402 (class 2606 OID 118750)
-- Dependencies: 273 273 2455
-- Name: PK_credit_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "PK_credit_id" PRIMARY KEY (credit_id);


--
-- TOC entry 2301 (class 2606 OID 62049)
-- Dependencies: 192 192 2455
-- Name: PK_dept_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "PK_dept_id" PRIMARY KEY (dept_id);


--
-- TOC entry 2294 (class 2606 OID 62019)
-- Dependencies: 188 188 2455
-- Name: PK_emp_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employee_cats
    ADD CONSTRAINT "PK_emp_cat_id" PRIMARY KEY (emp_cat_id);


--
-- TOC entry 2318 (class 2606 OID 62130)
-- Dependencies: 202 202 2455
-- Name: PK_emp_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "PK_emp_id" PRIMARY KEY (emp_id);


--
-- TOC entry 2362 (class 2606 OID 62647)
-- Dependencies: 230 230 2455
-- Name: PK_exam_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "PK_exam_id" PRIMARY KEY (exam_id);


--
-- TOC entry 2354 (class 2606 OID 62608)
-- Dependencies: 226 226 2455
-- Name: PK_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "PK_exam_type" PRIMARY KEY (exam_type_id);


--
-- TOC entry 2326 (class 2606 OID 62208)
-- Dependencies: 206 206 2455
-- Name: PK_fee_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY fee_items
    ADD CONSTRAINT "PK_fee_item_id" PRIMARY KEY (fee_item_id);


--
-- TOC entry 2312 (class 2606 OID 62102)
-- Dependencies: 198 198 2455
-- Name: PK_grade_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading
    ADD CONSTRAINT "PK_grade_id" PRIMARY KEY (grade_id);


--
-- TOC entry 2346 (class 2606 OID 62547)
-- Dependencies: 222 222 2455
-- Name: PK_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY guardians
    ADD CONSTRAINT "PK_guardian_id" PRIMARY KEY (guardian_id);


--
-- TOC entry 2376 (class 2606 OID 70608)
-- Dependencies: 243 243 2455
-- Name: PK_installment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY installment_options
    ADD CONSTRAINT "PK_installment_id" PRIMARY KEY (installment_id);


--
-- TOC entry 2366 (class 2606 OID 70287)
-- Dependencies: 233 233 2455
-- Name: PK_inv_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "PK_inv_id" PRIMARY KEY (inv_id);


--
-- TOC entry 2368 (class 2606 OID 70301)
-- Dependencies: 235 235 2455
-- Name: PK_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "PK_inv_item_id" PRIMARY KEY (inv_item_id);


--
-- TOC entry 2342 (class 2606 OID 62491)
-- Dependencies: 218 218 2455
-- Name: PK_medical_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "PK_medical_id" PRIMARY KEY (medical_id);


--
-- TOC entry 2334 (class 2606 OID 62330)
-- Dependencies: 212 212 2455
-- Name: PK_payment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "PK_payment_id" PRIMARY KEY (payment_id);


--
-- TOC entry 2374 (class 2606 OID 70464)
-- Dependencies: 239 239 2455
-- Name: PK_payment_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "PK_payment_inv_item_id" PRIMARY KEY (payment_inv_item_id);


--
-- TOC entry 2378 (class 2606 OID 70681)
-- Dependencies: 245 245 2455
-- Name: PK_payment_replace_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "PK_payment_replace_item_id" PRIMARY KEY (payment_replace_item_id);


--
-- TOC entry 2382 (class 2606 OID 90191)
-- Dependencies: 252 252 2455
-- Name: PK_perm_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_permissions
    ADD CONSTRAINT "PK_perm_id" PRIMARY KEY (perm_id);


--
-- TOC entry 2394 (class 2606 OID 100911)
-- Dependencies: 264 264 2455
-- Name: PK_post_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "PK_post_id" PRIMARY KEY (post_id);


--
-- TOC entry 2392 (class 2606 OID 100869)
-- Dependencies: 262 262 2455
-- Name: PK_post_status_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_post_statuses
    ADD CONSTRAINT "PK_post_status_id" PRIMARY KEY (post_status_id);


--
-- TOC entry 2388 (class 2606 OID 91262)
-- Dependencies: 258 258 2455
-- Name: PK_post_type_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY blog_post_types
    ADD CONSTRAINT "PK_post_type_id" PRIMARY KEY (post_type_id);


--
-- TOC entry 2292 (class 2606 OID 62008)
-- Dependencies: 186 186 2455
-- Name: PK_setting_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY settings
    ADD CONSTRAINT "PK_setting_name" PRIMARY KEY (name);


--
-- TOC entry 2370 (class 2606 OID 70433)
-- Dependencies: 237 237 2455
-- Name: PK_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "PK_student_fee_item" PRIMARY KEY (student_fee_item_id);


--
-- TOC entry 2384 (class 2606 OID 91044)
-- Dependencies: 254 254 2455
-- Name: PK_student_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "PK_student_guardian_id" PRIMARY KEY (student_guardian_id);


--
-- TOC entry 2322 (class 2606 OID 62177)
-- Dependencies: 204 204 2455
-- Name: PK_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "PK_student_id" PRIMARY KEY (student_id);


--
-- TOC entry 2308 (class 2606 OID 62084)
-- Dependencies: 196 196 2455
-- Name: PK_subject_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "PK_subject_id" PRIMARY KEY (subject_id);


--
-- TOC entry 2314 (class 2606 OID 62115)
-- Dependencies: 200 200 2455
-- Name: PK_term_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "PK_term_id" PRIMARY KEY (term_id);


--
-- TOC entry 2330 (class 2606 OID 62270)
-- Dependencies: 210 210 2455
-- Name: PK_transport_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "PK_transport_id" PRIMARY KEY (transport_id);


--
-- TOC entry 2336 (class 2606 OID 62446)
-- Dependencies: 214 214 2455
-- Name: PK_user_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "PK_user_id" PRIMARY KEY (user_id);


--
-- TOC entry 2296 (class 2606 OID 100835)
-- Dependencies: 188 188 188 2455
-- Name: U_active_emp_cat; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employee_cats
    ADD CONSTRAINT "U_active_emp_cat" UNIQUE (emp_cat_name, active);


--
-- TOC entry 2324 (class 2606 OID 62179)
-- Dependencies: 204 204 2455
-- Name: U_admission_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "U_admission_number" UNIQUE (admission_number);


--
-- TOC entry 2352 (class 2606 OID 92396)
-- Dependencies: 224 224 224 2455
-- Name: U_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "U_class_subject" UNIQUE (class_id, subject_id);


--
-- TOC entry 2303 (class 2606 OID 62051)
-- Dependencies: 192 192 2455
-- Name: U_dept_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "U_dept_name" UNIQUE (dept_name);


--
-- TOC entry 2320 (class 2606 OID 62132)
-- Dependencies: 202 202 2455
-- Name: U_emp_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "U_emp_number" UNIQUE (emp_number);


--
-- TOC entry 2356 (class 2606 OID 91548)
-- Dependencies: 226 226 226 2455
-- Name: U_exam_type_per_category; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "U_exam_type_per_category" UNIQUE (exam_type, class_cat_id);


--
-- TOC entry 2348 (class 2606 OID 91091)
-- Dependencies: 222 222 2455
-- Name: U_id_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY guardians
    ADD CONSTRAINT "U_id_number" UNIQUE (id_number);


--
-- TOC entry 2332 (class 2606 OID 62272)
-- Dependencies: 210 210 2455
-- Name: U_route; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "U_route" UNIQUE (route);


--
-- TOC entry 2364 (class 2606 OID 80811)
-- Dependencies: 230 230 230 230 2455
-- Name: U_student_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "U_student_exam_mark" UNIQUE (student_id, class_sub_exam_id, term_id);


--
-- TOC entry 2372 (class 2606 OID 70435)
-- Dependencies: 237 237 237 2455
-- Name: U_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "U_student_fee_item" UNIQUE (student_id, fee_item_id);


--
-- TOC entry 2310 (class 2606 OID 70920)
-- Dependencies: 196 196 196 2455
-- Name: U_subject_by_class_cat; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "U_subject_by_class_cat" UNIQUE (subject_name, class_cat_id);


--
-- TOC entry 2360 (class 2606 OID 82506)
-- Dependencies: 228 228 228 2455
-- Name: U_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "U_subject_exam" UNIQUE (class_subject_id, exam_type_id);


--
-- TOC entry 2316 (class 2606 OID 62117)
-- Dependencies: 200 200 200 2455
-- Name: U_term; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "U_term" UNIQUE (start_date, end_date);


--
-- TOC entry 2338 (class 2606 OID 62448)
-- Dependencies: 214 214 2455
-- Name: U_username; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 2340 (class 2606 OID 62456)
-- Dependencies: 216 216 2455
-- Name: countries_pk; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY countries
    ADD CONSTRAINT countries_pk PRIMARY KEY (countries_id);


--
-- TOC entry 2299 (class 1259 OID 117321)
-- Dependencies: 190 190 2455
-- Name: U_active_class_cat; Type: INDEX; Schema: app; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX "U_active_class_cat" ON class_cats USING btree (class_cat_name) WHERE (active IS TRUE);


--
-- TOC entry 2306 (class 1259 OID 117320)
-- Dependencies: 194 194 194 2455
-- Name: U_active_class_name; Type: INDEX; Schema: app; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX "U_active_class_name" ON classes USING btree (class_name, class_cat_id) WHERE (active IS TRUE);


--
-- TOC entry 2178 (class 2618 OID 70591)
-- Dependencies: 212 2366 212 233 233 233 233 233 233 212 240 2455
-- Name: _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE RULE "_RETURN" AS ON SELECT TO invoice_balances DO INSTEAD SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payments.amount), (0)::numeric) AS total_paid, (COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) AS balance, invoices.due_date, CASE WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true ELSE false END AS past_due, invoices.canceled FROM (invoices LEFT JOIN payments ON (((invoices.inv_id = payments.inv_id) AND (payments.reversed IS FALSE)))) GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 2183 (class 2618 OID 118107)
-- Dependencies: 212 239 239 239 235 233 235 2366 233 233 212 233 233 233 233 271 2455
-- Name: _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE RULE "_RETURN" AS ON SELECT TO invoice_balances2 DO INSTEAD SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payment_inv_items.amount), (0)::numeric) AS total_paid, (COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) AS balance, invoices.due_date, CASE WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payment_inv_items.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true ELSE false END AS past_due, invoices.canceled, invoices.term_id FROM (invoices JOIN (invoice_line_items LEFT JOIN (payment_inv_items JOIN payments ON (((payment_inv_items.payment_id = payments.payment_id) AND (payments.reversed IS FALSE)))) ON ((invoice_line_items.inv_item_id = payment_inv_items.inv_item_id))) ON ((invoices.inv_id = invoice_line_items.inv_id))) GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 2445 (class 2606 OID 117743)
-- Dependencies: 2397 268 270 2455
-- Name: FK_audience_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_audience_id" FOREIGN KEY (audience_id) REFERENCES communication_audience(audience_id);


--
-- TOC entry 2437 (class 2606 OID 91295)
-- Dependencies: 2304 256 194 2455
-- Name: FK_blog_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2438 (class 2606 OID 91300)
-- Dependencies: 202 256 2317 2455
-- Name: FK_blog_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blogs
    ADD CONSTRAINT "FK_blog_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2403 (class 2606 OID 91567)
-- Dependencies: 194 190 2297 2455
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2405 (class 2606 OID 119219)
-- Dependencies: 196 190 2297 2455
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2410 (class 2606 OID 62721)
-- Dependencies: 2304 208 194 2455
-- Name: FK_class_history_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2411 (class 2606 OID 62726)
-- Dependencies: 2321 208 204 2455
-- Name: FK_class_history_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2417 (class 2606 OID 79017)
-- Dependencies: 2353 226 228 2455
-- Name: FK_class_subect_exam_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subect_exam_type" FOREIGN KEY (exam_type_id) REFERENCES exam_types(exam_type_id);


--
-- TOC entry 2418 (class 2606 OID 79022)
-- Dependencies: 224 2349 228 2455
-- Name: FK_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subject" FOREIGN KEY (class_subject_id) REFERENCES class_subjects(class_subject_id);


--
-- TOC entry 2414 (class 2606 OID 79002)
-- Dependencies: 194 224 2304 2455
-- Name: FK_class_subject_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2419 (class 2606 OID 80795)
-- Dependencies: 228 2357 230 2455
-- Name: FK_class_subject_exam; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_class_subject_exam" FOREIGN KEY (class_sub_exam_id) REFERENCES class_subject_exams(class_sub_exam_id);


--
-- TOC entry 2415 (class 2606 OID 79007)
-- Dependencies: 224 2307 196 2455
-- Name: FK_class_subject_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_subject" FOREIGN KEY (subject_id) REFERENCES subjects(subject_id);


--
-- TOC entry 2404 (class 2606 OID 91572)
-- Dependencies: 194 2317 202 2455
-- Name: FK_class_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "FK_class_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2446 (class 2606 OID 117748)
-- Dependencies: 270 194 2304 2455
-- Name: FK_com_class_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_class_id" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2447 (class 2606 OID 117753)
-- Dependencies: 222 270 2345 2455
-- Name: FK_com_guardian_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_guardian_id" FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id);


--
-- TOC entry 2448 (class 2606 OID 117758)
-- Dependencies: 270 202 2317 2455
-- Name: FK_com_message_from; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_message_from" FOREIGN KEY (message_from) REFERENCES employees(emp_id);


--
-- TOC entry 2449 (class 2606 OID 117763)
-- Dependencies: 2321 204 270 2455
-- Name: FK_com_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2450 (class 2606 OID 117768)
-- Dependencies: 2389 270 260 2455
-- Name: FK_com_type_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_com_type_id" FOREIGN KEY (com_type_id) REFERENCES communication_types(com_type_id);


--
-- TOC entry 2453 (class 2606 OID 118904)
-- Dependencies: 2333 212 273 2455
-- Name: FK_credit_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "FK_credit_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2452 (class 2606 OID 118899)
-- Dependencies: 2321 204 273 2455
-- Name: FK_credit_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY credits
    ADD CONSTRAINT "FK_credit_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2451 (class 2606 OID 117773)
-- Dependencies: 270 2391 262 2455
-- Name: FK_email_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY communications
    ADD CONSTRAINT "FK_email_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2407 (class 2606 OID 117812)
-- Dependencies: 188 202 2293 2455
-- Name: FK_emp_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_cat_id" FOREIGN KEY (emp_cat_id) REFERENCES employee_cats(emp_cat_id);


--
-- TOC entry 2408 (class 2606 OID 117817)
-- Dependencies: 192 2300 202 2455
-- Name: FK_emp_dept_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_dept_id" FOREIGN KEY (dept_id) REFERENCES departments(dept_id);


--
-- TOC entry 2420 (class 2606 OID 80800)
-- Dependencies: 2321 204 230 2455
-- Name: FK_exam_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2421 (class 2606 OID 80805)
-- Dependencies: 230 2313 200 2455
-- Name: FK_exam_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_term" FOREIGN KEY (term_id) REFERENCES terms(term_id);


--
-- TOC entry 2416 (class 2606 OID 91549)
-- Dependencies: 2297 190 226 2455
-- Name: FK_exam_type_class_cat; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "FK_exam_type_class_cat" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2443 (class 2606 OID 117309)
-- Dependencies: 266 2349 224 2455
-- Name: FK_homework_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_class_subject" FOREIGN KEY (class_subject_id) REFERENCES class_subjects(class_subject_id);


--
-- TOC entry 2444 (class 2606 OID 117314)
-- Dependencies: 2391 266 262 2455
-- Name: FK_homework_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY homework
    ADD CONSTRAINT "FK_homework_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2409 (class 2606 OID 91506)
-- Dependencies: 243 204 2375 2455
-- Name: FK_installment_option; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "FK_installment_option" FOREIGN KEY (installment_option_id) REFERENCES installment_options(installment_id);


--
-- TOC entry 2423 (class 2606 OID 70507)
-- Dependencies: 2369 237 235 2455
-- Name: FK_inv_item_fee_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_fee_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2424 (class 2606 OID 70512)
-- Dependencies: 2365 235 233 2455
-- Name: FK_inv_item_invoice; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_invoice" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2422 (class 2606 OID 70662)
-- Dependencies: 2321 233 204 2455
-- Name: FK_invoice_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "FK_invoice_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2427 (class 2606 OID 70527)
-- Dependencies: 239 212 2333 2455
-- Name: FK_payment_fee_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_fee_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2428 (class 2606 OID 70532)
-- Dependencies: 233 239 2365 2455
-- Name: FK_payment_inv; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2429 (class 2606 OID 70537)
-- Dependencies: 239 235 2367 2455
-- Name: FK_payment_inv_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv_item" FOREIGN KEY (inv_item_id) REFERENCES invoice_line_items(inv_item_id);


--
-- TOC entry 2430 (class 2606 OID 70810)
-- Dependencies: 2369 237 245 2455
-- Name: FK_payment_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2431 (class 2606 OID 70815)
-- Dependencies: 212 245 2333 2455
-- Name: FK_payment_replace_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_replace_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2412 (class 2606 OID 118909)
-- Dependencies: 212 204 2321 2455
-- Name: FK_payments_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "FK_payments_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2439 (class 2606 OID 117822)
-- Dependencies: 2385 256 264 2455
-- Name: FK_post_blog; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_blog" FOREIGN KEY (blog_id) REFERENCES blogs(blog_id);


--
-- TOC entry 2440 (class 2606 OID 117827)
-- Dependencies: 262 264 2391 2455
-- Name: FK_post_status; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_status" FOREIGN KEY (post_status_id) REFERENCES blog_post_statuses(post_status_id);


--
-- TOC entry 2441 (class 2606 OID 117832)
-- Dependencies: 258 2387 264 2455
-- Name: FK_post_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_post_type" FOREIGN KEY (post_type_id) REFERENCES blog_post_types(post_type_id);


--
-- TOC entry 2442 (class 2606 OID 117837)
-- Dependencies: 264 2317 202 2455
-- Name: FK_posted_by; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY blog_posts
    ADD CONSTRAINT "FK_posted_by" FOREIGN KEY (created_by) REFERENCES employees(emp_id);


--
-- TOC entry 2432 (class 2606 OID 119605)
-- Dependencies: 194 2304 249 2455
-- Name: FK_report_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2433 (class 2606 OID 119610)
-- Dependencies: 2321 204 249 2455
-- Name: FK_report_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2434 (class 2606 OID 119615)
-- Dependencies: 2313 200 249 2455
-- Name: FK_report_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY report_cards
    ADD CONSTRAINT "FK_report_term" FOREIGN KEY (term_id) REFERENCES terms(term_id);


--
-- TOC entry 2425 (class 2606 OID 70550)
-- Dependencies: 237 206 2325 2455
-- Name: FK_student_fee_items; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items" FOREIGN KEY (fee_item_id) REFERENCES fee_items(fee_item_id);


--
-- TOC entry 2426 (class 2606 OID 70555)
-- Dependencies: 237 2321 204 2455
-- Name: FK_student_fee_items_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2435 (class 2606 OID 91059)
-- Dependencies: 222 254 2345 2455
-- Name: FK_student_guardian_guardian; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "FK_student_guardian_guardian" FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id);


--
-- TOC entry 2436 (class 2606 OID 91064)
-- Dependencies: 204 2321 254 2455
-- Name: FK_student_guardian_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2413 (class 2606 OID 70191)
-- Dependencies: 204 218 2321 2455
-- Name: FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2406 (class 2606 OID 119224)
-- Dependencies: 2317 196 202 2455
-- Name: FK_subject_teacher; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "FK_subject_teacher" FOREIGN KEY (teacher_id) REFERENCES employees(emp_id);


--
-- TOC entry 2460 (class 0 OID 0)
-- Dependencies: 5
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2016-11-01 15:53:42

--
-- PostgreSQL database dump complete
--

