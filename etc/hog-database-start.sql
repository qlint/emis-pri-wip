--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.8
-- Dumped by pg_dump version 9.1.8
-- Started on 2016-04-27 12:00:09

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 2280 (class 1262 OID 61999)
-- Name: eduweb_hog; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE eduweb_hog WITH TEMPLATE = template0 ENCODING = 'UTF8' LC_COLLATE = 'English_Canada.1252' LC_CTYPE = 'English_Canada.1252';


ALTER DATABASE eduweb_hog OWNER TO postgres;

\connect eduweb_hog

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
-- TOC entry 238 (class 3079 OID 11638)
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- TOC entry 2283 (class 0 OID 0)
-- Dependencies: 238
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = app, pg_catalog;

--
-- TOC entry 250 (class 1255 OID 79027)
-- Dependencies: 7 719
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

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 178 (class 1259 OID 62022)
-- Dependencies: 7
-- Name: class_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE class_cats (
    class_cat_id integer NOT NULL,
    class_cat_name character varying NOT NULL
);


ALTER TABLE app.class_cats OWNER TO postgres;

--
-- TOC entry 177 (class 1259 OID 62020)
-- Dependencies: 178 7
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
-- TOC entry 2284 (class 0 OID 0)
-- Dependencies: 177
-- Name: class_cats_class_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_cats_class_cat_id_seq OWNED BY class_cats.class_cat_id;


--
-- TOC entry 218 (class 1259 OID 62621)
-- Dependencies: 2147 2148 7
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
-- TOC entry 217 (class 1259 OID 62619)
-- Dependencies: 7 218
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
-- TOC entry 2285 (class 0 OID 0)
-- Dependencies: 217
-- Name: class_subject_exams_class_sub_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subject_exams_class_sub_exam_id_seq OWNED BY class_subject_exams.class_sub_exam_id;


--
-- TOC entry 214 (class 1259 OID 62582)
-- Dependencies: 2142 2143 7
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
-- TOC entry 213 (class 1259 OID 62580)
-- Dependencies: 214 7
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
-- TOC entry 2286 (class 0 OID 0)
-- Dependencies: 213
-- Name: class_subjects_class_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE class_subjects_class_subject_id_seq OWNED BY class_subjects.class_subject_id;


--
-- TOC entry 182 (class 1259 OID 62054)
-- Dependencies: 2090 2091 7
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
    modified_by integer
);


ALTER TABLE app.classes OWNER TO postgres;

--
-- TOC entry 181 (class 1259 OID 62052)
-- Dependencies: 182 7
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
-- TOC entry 2287 (class 0 OID 0)
-- Dependencies: 181
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE classes_class_id_seq OWNED BY classes.class_id;


--
-- TOC entry 206 (class 1259 OID 62451)
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
-- TOC entry 205 (class 1259 OID 62449)
-- Dependencies: 206 7
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
-- TOC entry 2288 (class 0 OID 0)
-- Dependencies: 205
-- Name: countries_countries_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE countries_countries_id_seq OWNED BY countries.countries_id;


--
-- TOC entry 188 (class 1259 OID 62106)
-- Dependencies: 2097 7
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
-- TOC entry 221 (class 1259 OID 70208)
-- Dependencies: 2079 7
-- Name: current_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW current_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now() >= date_trunc('month'::text, (terms.start_date)::timestamp with time zone)) AND (now() <= ((terms.end_date + '1 mon'::interval) - '1 day'::interval)));


ALTER TABLE app.current_term OWNER TO postgres;

--
-- TOC entry 180 (class 1259 OID 62039)
-- Dependencies: 2087 2088 7
-- Name: departments; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE departments (
    dept_id integer NOT NULL,
    dept_name character varying NOT NULL,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL,
    category character varying
);


ALTER TABLE app.departments OWNER TO postgres;

--
-- TOC entry 179 (class 1259 OID 62037)
-- Dependencies: 180 7
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
-- TOC entry 2289 (class 0 OID 0)
-- Dependencies: 179
-- Name: departments_dept_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE departments_dept_id_seq OWNED BY departments.dept_id;


--
-- TOC entry 176 (class 1259 OID 62011)
-- Dependencies: 7
-- Name: employee_cats; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE employee_cats (
    emp_cat_id integer NOT NULL,
    emp_cat_name character varying NOT NULL
);


ALTER TABLE app.employee_cats OWNER TO postgres;

--
-- TOC entry 175 (class 1259 OID 62009)
-- Dependencies: 176 7
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
-- TOC entry 2290 (class 0 OID 0)
-- Dependencies: 175
-- Name: employee_cats_emp_cat_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employee_cats_emp_cat_id_seq OWNED BY employee_cats.emp_cat_id;


--
-- TOC entry 190 (class 1259 OID 62120)
-- Dependencies: 2099 2100 7
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
    middle_name character varying NOT NULL,
    last_name character varying NOT NULL,
    initials character varying,
    dob date,
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
    emp_image character varying
);


ALTER TABLE app.employees OWNER TO postgres;

--
-- TOC entry 189 (class 1259 OID 62118)
-- Dependencies: 7 190
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
-- TOC entry 2291 (class 0 OID 0)
-- Dependencies: 189
-- Name: employees_emp_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE employees_emp_id_seq OWNED BY employees.emp_id;


--
-- TOC entry 220 (class 1259 OID 62641)
-- Dependencies: 2150 7
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
-- TOC entry 219 (class 1259 OID 62639)
-- Dependencies: 7 220
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
-- TOC entry 2292 (class 0 OID 0)
-- Dependencies: 219
-- Name: exam_marks_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_marks_exam_id_seq OWNED BY exam_marks.exam_id;


--
-- TOC entry 216 (class 1259 OID 62600)
-- Dependencies: 2145 7
-- Name: exam_types; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE exam_types (
    exam_type_id integer NOT NULL,
    exam_type character varying NOT NULL,
    class_cat_id integer,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    modified_date timestamp without time zone,
    modified_by integer
);


ALTER TABLE app.exam_types OWNER TO postgres;

--
-- TOC entry 215 (class 1259 OID 62598)
-- Dependencies: 7 216
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
-- TOC entry 2293 (class 0 OID 0)
-- Dependencies: 215
-- Name: exam_types_exam_type_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE exam_types_exam_type_id_seq OWNED BY exam_types.exam_type_id;


--
-- TOC entry 194 (class 1259 OID 62198)
-- Dependencies: 2114 2115 2116 2117 2118 7
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
-- TOC entry 193 (class 1259 OID 62196)
-- Dependencies: 194 7
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
-- TOC entry 2294 (class 0 OID 0)
-- Dependencies: 193
-- Name: fee_items_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE fee_items_fee_item_id_seq OWNED BY fee_items.fee_item_id;


--
-- TOC entry 186 (class 1259 OID 62094)
-- Dependencies: 7
-- Name: grading; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE grading (
    grade_id integer NOT NULL,
    grade character varying NOT NULL,
    mark_range character varying NOT NULL
);


ALTER TABLE app.grading OWNER TO postgres;

--
-- TOC entry 185 (class 1259 OID 62092)
-- Dependencies: 7 186
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
-- TOC entry 2295 (class 0 OID 0)
-- Dependencies: 185
-- Name: grading_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE grading_grade_id_seq OWNED BY grading.grade_id;


--
-- TOC entry 233 (class 1259 OID 70599)
-- Dependencies: 2163 7
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
-- TOC entry 2296 (class 0 OID 0)
-- Dependencies: 233
-- Name: COLUMN installment_options.payment_interval; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN installment_options.payment_interval IS 'number of days';


--
-- TOC entry 232 (class 1259 OID 70597)
-- Dependencies: 233 7
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
-- TOC entry 2297 (class 0 OID 0)
-- Dependencies: 232
-- Name: installment_options_installment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE installment_options_installment_id_seq OWNED BY installment_options.installment_id;


--
-- TOC entry 230 (class 1259 OID 70588)
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
-- TOC entry 225 (class 1259 OID 70295)
-- Dependencies: 2156 7
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
-- TOC entry 224 (class 1259 OID 70293)
-- Dependencies: 7 225
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
-- TOC entry 2298 (class 0 OID 0)
-- Dependencies: 224
-- Name: invoice_line_items_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoice_line_items_inv_item_id_seq OWNED BY invoice_line_items.inv_item_id;


--
-- TOC entry 223 (class 1259 OID 70280)
-- Dependencies: 2152 2153 2154 7
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
    canceled boolean DEFAULT false NOT NULL
);


ALTER TABLE app.invoices OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 70278)
-- Dependencies: 223 7
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
-- TOC entry 2299 (class 0 OID 0)
-- Dependencies: 222
-- Name: invoices_inv_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE invoices_inv_id_seq OWNED BY invoices.inv_id;


--
-- TOC entry 210 (class 1259 OID 62499)
-- Dependencies: 7
-- Name: medical_conditions; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE medical_conditions (
    condition_id integer NOT NULL,
    illness_condition character varying NOT NULL
);


ALTER TABLE app.medical_conditions OWNER TO postgres;

--
-- TOC entry 209 (class 1259 OID 62497)
-- Dependencies: 7 210
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
-- TOC entry 2300 (class 0 OID 0)
-- Dependencies: 209
-- Name: medical_conditions_condition_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE medical_conditions_condition_id_seq OWNED BY medical_conditions.condition_id;


--
-- TOC entry 202 (class 1259 OID 62407)
-- Dependencies: 2130 2131 7
-- Name: news; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE news (
    news_id integer NOT NULL,
    news_date date,
    audience character varying NOT NULL,
    new_type character varying NOT NULL,
    title character varying NOT NULL,
    message character varying NOT NULL,
    attachment character varying NOT NULL,
    student_id integer,
    send_as_email boolean,
    post_to_parent_portal boolean,
    creation_date timestamp without time zone DEFAULT now() NOT NULL,
    created_by integer,
    active boolean DEFAULT true NOT NULL
);


ALTER TABLE app.news OWNER TO postgres;

--
-- TOC entry 201 (class 1259 OID 62405)
-- Dependencies: 7 202
-- Name: news_news_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE news_news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.news_news_id_seq OWNER TO postgres;

--
-- TOC entry 2301 (class 0 OID 0)
-- Dependencies: 201
-- Name: news_news_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE news_news_id_seq OWNED BY news.news_id;


--
-- TOC entry 231 (class 1259 OID 70593)
-- Dependencies: 2081 7
-- Name: next_term; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW next_term AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date LIMIT 1;


ALTER TABLE app.next_term OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 70458)
-- Dependencies: 2161 7
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
-- TOC entry 228 (class 1259 OID 70456)
-- Dependencies: 229 7
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
-- TOC entry 2302 (class 0 OID 0)
-- Dependencies: 228
-- Name: payment_inv_items_payment_inv_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_inv_items_payment_inv_item_id_seq OWNED BY payment_inv_items.payment_inv_item_id;


--
-- TOC entry 235 (class 1259 OID 70672)
-- Dependencies: 2165 7
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
-- TOC entry 234 (class 1259 OID 70670)
-- Dependencies: 235 7
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
-- TOC entry 2303 (class 0 OID 0)
-- Dependencies: 234
-- Name: payment_replacement_items_payment_replace_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payment_replacement_items_payment_replace_item_id_seq OWNED BY payment_replacement_items.payment_replace_item_id;


--
-- TOC entry 200 (class 1259 OID 62319)
-- Dependencies: 2126 2127 2128 7
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
-- TOC entry 2304 (class 0 OID 0)
-- Dependencies: 200
-- Name: COLUMN payments.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN payments.payment_method IS 'Cash or Cheque';


--
-- TOC entry 199 (class 1259 OID 62317)
-- Dependencies: 200 7
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
-- TOC entry 2305 (class 0 OID 0)
-- Dependencies: 199
-- Name: payments_payment_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE payments_payment_id_seq OWNED BY payments.payment_id;


--
-- TOC entry 174 (class 1259 OID 62001)
-- Dependencies: 7
-- Name: settings; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE settings (
    name character varying NOT NULL,
    value character varying NOT NULL
);


ALTER TABLE app.settings OWNER TO postgres;

--
-- TOC entry 196 (class 1259 OID 62215)
-- Dependencies: 2120 2121 7
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
-- TOC entry 195 (class 1259 OID 62213)
-- Dependencies: 7 196
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
-- TOC entry 2306 (class 0 OID 0)
-- Dependencies: 195
-- Name: student_class_history_class_history_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_class_history_class_history_id_seq OWNED BY student_class_history.class_history_id;


--
-- TOC entry 192 (class 1259 OID 62166)
-- Dependencies: 2102 2103 2104 2105 2106 2107 2108 2109 2110 2111 2112 7
-- Name: students; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE students (
    student_id integer NOT NULL,
    admission_number character varying,
    gender character(1),
    first_name character varying NOT NULL,
    middle_name character varying NOT NULL,
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
-- TOC entry 184 (class 1259 OID 62074)
-- Dependencies: 2093 2094 7
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
    modified_by integer
);


ALTER TABLE app.subjects OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 79248)
-- Dependencies: 2083 7
-- Name: student_exam_marks; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW student_exam_marks AS
    SELECT students.student_id, (((((students.first_name)::text || ' '::text) || (COALESCE(students.middle_name, ''::character varying))::text) || ' '::text) || (students.last_name)::text) AS student_name, exam_marks.term_id, class_subjects.class_id, class_subject_exams.exam_type_id, exam_types.exam_type, subjects.subject_name, exam_marks.mark, class_subject_exams.class_sub_exam_id, class_subject_exams.grade_weight FROM ((((((class_subjects JOIN class_subject_exams USING (class_subject_id)) JOIN exam_types USING (exam_type_id)) JOIN subjects USING (subject_id)) JOIN classes USING (class_id)) JOIN students ON ((classes.class_id = students.current_class))) LEFT JOIN exam_marks ON (((students.student_id = exam_marks.student_id) AND (class_subject_exams.class_sub_exam_id = exam_marks.class_sub_exam_id))));


ALTER TABLE app.student_exam_marks OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 70423)
-- Dependencies: 2158 2159 7
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
-- TOC entry 2307 (class 0 OID 0)
-- Dependencies: 227
-- Name: COLUMN student_fee_items.payment_method; Type: COMMENT; Schema: app; Owner: postgres
--

COMMENT ON COLUMN student_fee_items.payment_method IS 'This is an option from the Payment Options setting';


--
-- TOC entry 226 (class 1259 OID 70421)
-- Dependencies: 7 227
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
-- TOC entry 2308 (class 0 OID 0)
-- Dependencies: 226
-- Name: student_fee_items_student_fee_item_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_fee_items_student_fee_item_id_seq OWNED BY student_fee_items.student_fee_item_id;


--
-- TOC entry 212 (class 1259 OID 62537)
-- Dependencies: 2139 2140 7
-- Name: student_guardians; Type: TABLE; Schema: app; Owner: postgres; Tablespace: 
--

CREATE TABLE student_guardians (
    guardian_id integer NOT NULL,
    student_id integer NOT NULL,
    first_name character varying NOT NULL,
    middle_name character varying NOT NULL,
    last_name character varying NOT NULL,
    id_number character varying,
    telephone character varying,
    email character varying,
    relationship character varying,
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


ALTER TABLE app.student_guardians OWNER TO postgres;

--
-- TOC entry 211 (class 1259 OID 62535)
-- Dependencies: 212 7
-- Name: student_guardians_guardian_id_seq; Type: SEQUENCE; Schema: app; Owner: postgres
--

CREATE SEQUENCE student_guardians_guardian_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE app.student_guardians_guardian_id_seq OWNER TO postgres;

--
-- TOC entry 2309 (class 0 OID 0)
-- Dependencies: 211
-- Name: student_guardians_guardian_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_guardians_guardian_id_seq OWNED BY student_guardians.guardian_id;


--
-- TOC entry 208 (class 1259 OID 62483)
-- Dependencies: 2136 7
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
-- TOC entry 207 (class 1259 OID 62481)
-- Dependencies: 7 208
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
-- TOC entry 2310 (class 0 OID 0)
-- Dependencies: 207
-- Name: student_medical_history_medical_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE student_medical_history_medical_id_seq OWNED BY student_medical_history.medical_id;


--
-- TOC entry 191 (class 1259 OID 62164)
-- Dependencies: 7 192
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
-- TOC entry 2311 (class 0 OID 0)
-- Dependencies: 191
-- Name: students_student_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE students_student_id_seq OWNED BY students.student_id;


--
-- TOC entry 183 (class 1259 OID 62072)
-- Dependencies: 7 184
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
-- TOC entry 2312 (class 0 OID 0)
-- Dependencies: 183
-- Name: subjects_subject_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE subjects_subject_id_seq OWNED BY subjects.subject_id;


--
-- TOC entry 236 (class 1259 OID 70800)
-- Dependencies: 2082 7
-- Name: term_after_next; Type: VIEW; Schema: app; Owner: postgres
--

CREATE VIEW term_after_next AS
    SELECT terms.term_id, terms.term_name, terms.start_date, terms.end_date, terms.creation_date, terms.created_by, terms.term_number FROM terms WHERE ((now())::date < terms.start_date) ORDER BY terms.start_date OFFSET 1 LIMIT 1;


ALTER TABLE app.term_after_next OWNER TO postgres;

--
-- TOC entry 187 (class 1259 OID 62104)
-- Dependencies: 7 188
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
-- TOC entry 2313 (class 0 OID 0)
-- Dependencies: 187
-- Name: terms_term_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE terms_term_id_seq OWNED BY terms.term_id;


--
-- TOC entry 198 (class 1259 OID 62260)
-- Dependencies: 2123 2124 7
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
-- TOC entry 197 (class 1259 OID 62258)
-- Dependencies: 198 7
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
-- TOC entry 2314 (class 0 OID 0)
-- Dependencies: 197
-- Name: transport_routes_transport_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE transport_routes_transport_id_seq OWNED BY transport_routes.transport_id;


--
-- TOC entry 204 (class 1259 OID 62437)
-- Dependencies: 2133 7
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
    user_type character varying NOT NULL
);


ALTER TABLE app.users OWNER TO postgres;

--
-- TOC entry 203 (class 1259 OID 62435)
-- Dependencies: 7 204
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
-- TOC entry 2315 (class 0 OID 0)
-- Dependencies: 203
-- Name: user_user_id_seq; Type: SEQUENCE OWNED BY; Schema: app; Owner: postgres
--

ALTER SEQUENCE user_user_id_seq OWNED BY users.user_id;


--
-- TOC entry 2085 (class 2604 OID 62025)
-- Dependencies: 177 178 178
-- Name: class_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_cats ALTER COLUMN class_cat_id SET DEFAULT nextval('class_cats_class_cat_id_seq'::regclass);


--
-- TOC entry 2146 (class 2604 OID 62624)
-- Dependencies: 217 218 218
-- Name: class_sub_exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams ALTER COLUMN class_sub_exam_id SET DEFAULT nextval('class_subject_exams_class_sub_exam_id_seq'::regclass);


--
-- TOC entry 2141 (class 2604 OID 62585)
-- Dependencies: 214 213 214
-- Name: class_subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects ALTER COLUMN class_subject_id SET DEFAULT nextval('class_subjects_class_subject_id_seq'::regclass);


--
-- TOC entry 2089 (class 2604 OID 62057)
-- Dependencies: 181 182 182
-- Name: class_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes ALTER COLUMN class_id SET DEFAULT nextval('classes_class_id_seq'::regclass);


--
-- TOC entry 2134 (class 2604 OID 62454)
-- Dependencies: 206 205 206
-- Name: countries_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY countries ALTER COLUMN countries_id SET DEFAULT nextval('countries_countries_id_seq'::regclass);


--
-- TOC entry 2086 (class 2604 OID 62042)
-- Dependencies: 180 179 180
-- Name: dept_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY departments ALTER COLUMN dept_id SET DEFAULT nextval('departments_dept_id_seq'::regclass);


--
-- TOC entry 2084 (class 2604 OID 62014)
-- Dependencies: 176 175 176
-- Name: emp_cat_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employee_cats ALTER COLUMN emp_cat_id SET DEFAULT nextval('employee_cats_emp_cat_id_seq'::regclass);


--
-- TOC entry 2098 (class 2604 OID 62123)
-- Dependencies: 189 190 190
-- Name: emp_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees ALTER COLUMN emp_id SET DEFAULT nextval('employees_emp_id_seq'::regclass);


--
-- TOC entry 2149 (class 2604 OID 62644)
-- Dependencies: 220 219 220
-- Name: exam_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks ALTER COLUMN exam_id SET DEFAULT nextval('exam_marks_exam_id_seq'::regclass);


--
-- TOC entry 2144 (class 2604 OID 62603)
-- Dependencies: 215 216 216
-- Name: exam_type_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types ALTER COLUMN exam_type_id SET DEFAULT nextval('exam_types_exam_type_id_seq'::regclass);


--
-- TOC entry 2113 (class 2604 OID 62201)
-- Dependencies: 193 194 194
-- Name: fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY fee_items ALTER COLUMN fee_item_id SET DEFAULT nextval('fee_items_fee_item_id_seq'::regclass);


--
-- TOC entry 2095 (class 2604 OID 62097)
-- Dependencies: 186 185 186
-- Name: grade_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY grading ALTER COLUMN grade_id SET DEFAULT nextval('grading_grade_id_seq'::regclass);


--
-- TOC entry 2162 (class 2604 OID 70602)
-- Dependencies: 232 233 233
-- Name: installment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY installment_options ALTER COLUMN installment_id SET DEFAULT nextval('installment_options_installment_id_seq'::regclass);


--
-- TOC entry 2155 (class 2604 OID 70298)
-- Dependencies: 225 224 225
-- Name: inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items ALTER COLUMN inv_item_id SET DEFAULT nextval('invoice_line_items_inv_item_id_seq'::regclass);


--
-- TOC entry 2151 (class 2604 OID 70283)
-- Dependencies: 223 222 223
-- Name: inv_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices ALTER COLUMN inv_id SET DEFAULT nextval('invoices_inv_id_seq'::regclass);


--
-- TOC entry 2137 (class 2604 OID 62502)
-- Dependencies: 210 209 210
-- Name: condition_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY medical_conditions ALTER COLUMN condition_id SET DEFAULT nextval('medical_conditions_condition_id_seq'::regclass);


--
-- TOC entry 2129 (class 2604 OID 62410)
-- Dependencies: 202 201 202
-- Name: news_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY news ALTER COLUMN news_id SET DEFAULT nextval('news_news_id_seq'::regclass);


--
-- TOC entry 2160 (class 2604 OID 70461)
-- Dependencies: 229 228 229
-- Name: payment_inv_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items ALTER COLUMN payment_inv_item_id SET DEFAULT nextval('payment_inv_items_payment_inv_item_id_seq'::regclass);


--
-- TOC entry 2164 (class 2604 OID 70675)
-- Dependencies: 234 235 235
-- Name: payment_replace_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items ALTER COLUMN payment_replace_item_id SET DEFAULT nextval('payment_replacement_items_payment_replace_item_id_seq'::regclass);


--
-- TOC entry 2125 (class 2604 OID 62322)
-- Dependencies: 199 200 200
-- Name: payment_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments ALTER COLUMN payment_id SET DEFAULT nextval('payments_payment_id_seq'::regclass);


--
-- TOC entry 2119 (class 2604 OID 62218)
-- Dependencies: 196 195 196
-- Name: class_history_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history ALTER COLUMN class_history_id SET DEFAULT nextval('student_class_history_class_history_id_seq'::regclass);


--
-- TOC entry 2157 (class 2604 OID 70426)
-- Dependencies: 226 227 227
-- Name: student_fee_item_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items ALTER COLUMN student_fee_item_id SET DEFAULT nextval('student_fee_items_student_fee_item_id_seq'::regclass);


--
-- TOC entry 2138 (class 2604 OID 62540)
-- Dependencies: 211 212 212
-- Name: guardian_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians ALTER COLUMN guardian_id SET DEFAULT nextval('student_guardians_guardian_id_seq'::regclass);


--
-- TOC entry 2135 (class 2604 OID 62486)
-- Dependencies: 208 207 208
-- Name: medical_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history ALTER COLUMN medical_id SET DEFAULT nextval('student_medical_history_medical_id_seq'::regclass);


--
-- TOC entry 2101 (class 2604 OID 62169)
-- Dependencies: 192 191 192
-- Name: student_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students ALTER COLUMN student_id SET DEFAULT nextval('students_student_id_seq'::regclass);


--
-- TOC entry 2092 (class 2604 OID 62077)
-- Dependencies: 184 183 184
-- Name: subject_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects ALTER COLUMN subject_id SET DEFAULT nextval('subjects_subject_id_seq'::regclass);


--
-- TOC entry 2096 (class 2604 OID 62109)
-- Dependencies: 188 187 188
-- Name: term_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY terms ALTER COLUMN term_id SET DEFAULT nextval('terms_term_id_seq'::regclass);


--
-- TOC entry 2122 (class 2604 OID 62263)
-- Dependencies: 197 198 198
-- Name: transport_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY transport_routes ALTER COLUMN transport_id SET DEFAULT nextval('transport_routes_transport_id_seq'::regclass);


--
-- TOC entry 2132 (class 2604 OID 62440)
-- Dependencies: 204 203 204
-- Name: user_id; Type: DEFAULT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY users ALTER COLUMN user_id SET DEFAULT nextval('user_user_id_seq'::regclass);


--
-- TOC entry 2171 (class 2606 OID 62030)
-- Dependencies: 178 178 2277
-- Name: PK_class_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_cats
    ADD CONSTRAINT "PK_class_cat_id" PRIMARY KEY (class_cat_id);


--
-- TOC entry 2201 (class 2606 OID 62221)
-- Dependencies: 196 196 2277
-- Name: PK_class_history_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "PK_class_history_id" PRIMARY KEY (class_history_id);


--
-- TOC entry 2177 (class 2606 OID 62064)
-- Dependencies: 182 182 2277
-- Name: PK_class_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "PK_class_id" PRIMARY KEY (class_id);


--
-- TOC entry 2223 (class 2606 OID 62587)
-- Dependencies: 214 214 2277
-- Name: PK_class_subject; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "PK_class_subject" PRIMARY KEY (class_subject_id);


--
-- TOC entry 2229 (class 2606 OID 62626)
-- Dependencies: 218 218 2277
-- Name: PK_class_subject_exam; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "PK_class_subject_exam" PRIMARY KEY (class_sub_exam_id);


--
-- TOC entry 2219 (class 2606 OID 62507)
-- Dependencies: 210 210 2277
-- Name: PK_condition_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY medical_conditions
    ADD CONSTRAINT "PK_condition_id" PRIMARY KEY (condition_id);


--
-- TOC entry 2173 (class 2606 OID 62049)
-- Dependencies: 180 180 2277
-- Name: PK_dept_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "PK_dept_id" PRIMARY KEY (dept_id);


--
-- TOC entry 2169 (class 2606 OID 62019)
-- Dependencies: 176 176 2277
-- Name: PK_emp_cat_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employee_cats
    ADD CONSTRAINT "PK_emp_cat_id" PRIMARY KEY (emp_cat_id);


--
-- TOC entry 2191 (class 2606 OID 62130)
-- Dependencies: 190 190 2277
-- Name: PK_emp_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "PK_emp_id" PRIMARY KEY (emp_id);


--
-- TOC entry 2231 (class 2606 OID 62647)
-- Dependencies: 220 220 2277
-- Name: PK_exam_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "PK_exam_id" PRIMARY KEY (exam_id);


--
-- TOC entry 2225 (class 2606 OID 62608)
-- Dependencies: 216 216 2277
-- Name: PK_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "PK_exam_type" PRIMARY KEY (exam_type_id);


--
-- TOC entry 2199 (class 2606 OID 62208)
-- Dependencies: 194 194 2277
-- Name: PK_fee_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY fee_items
    ADD CONSTRAINT "PK_fee_item_id" PRIMARY KEY (fee_item_id);


--
-- TOC entry 2185 (class 2606 OID 62102)
-- Dependencies: 186 186 2277
-- Name: PK_grade_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grading
    ADD CONSTRAINT "PK_grade_id" PRIMARY KEY (grade_id);


--
-- TOC entry 2221 (class 2606 OID 62547)
-- Dependencies: 212 212 2277
-- Name: PK_guardian_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "PK_guardian_id" PRIMARY KEY (guardian_id);


--
-- TOC entry 2245 (class 2606 OID 70608)
-- Dependencies: 233 233 2277
-- Name: PK_installment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY installment_options
    ADD CONSTRAINT "PK_installment_id" PRIMARY KEY (installment_id);


--
-- TOC entry 2235 (class 2606 OID 70287)
-- Dependencies: 223 223 2277
-- Name: PK_inv_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "PK_inv_id" PRIMARY KEY (inv_id);


--
-- TOC entry 2237 (class 2606 OID 70301)
-- Dependencies: 225 225 2277
-- Name: PK_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "PK_inv_item_id" PRIMARY KEY (inv_item_id);


--
-- TOC entry 2217 (class 2606 OID 62491)
-- Dependencies: 208 208 2277
-- Name: PK_medical_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "PK_medical_id" PRIMARY KEY (medical_id);


--
-- TOC entry 2209 (class 2606 OID 62416)
-- Dependencies: 202 202 2277
-- Name: PK_news_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY news
    ADD CONSTRAINT "PK_news_id" PRIMARY KEY (news_id);


--
-- TOC entry 2207 (class 2606 OID 62330)
-- Dependencies: 200 200 2277
-- Name: PK_payment_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "PK_payment_id" PRIMARY KEY (payment_id);


--
-- TOC entry 2243 (class 2606 OID 70464)
-- Dependencies: 229 229 2277
-- Name: PK_payment_inv_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "PK_payment_inv_item_id" PRIMARY KEY (payment_inv_item_id);


--
-- TOC entry 2247 (class 2606 OID 70681)
-- Dependencies: 235 235 2277
-- Name: PK_payment_replace_item_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "PK_payment_replace_item_id" PRIMARY KEY (payment_replace_item_id);


--
-- TOC entry 2167 (class 2606 OID 62008)
-- Dependencies: 174 174 2277
-- Name: PK_setting_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY settings
    ADD CONSTRAINT "PK_setting_name" PRIMARY KEY (name);


--
-- TOC entry 2239 (class 2606 OID 70433)
-- Dependencies: 227 227 2277
-- Name: PK_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "PK_student_fee_item" PRIMARY KEY (student_fee_item_id);


--
-- TOC entry 2195 (class 2606 OID 62177)
-- Dependencies: 192 192 2277
-- Name: PK_student_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "PK_student_id" PRIMARY KEY (student_id);


--
-- TOC entry 2181 (class 2606 OID 62084)
-- Dependencies: 184 184 2277
-- Name: PK_subject_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "PK_subject_id" PRIMARY KEY (subject_id);


--
-- TOC entry 2187 (class 2606 OID 62115)
-- Dependencies: 188 188 2277
-- Name: PK_term_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "PK_term_id" PRIMARY KEY (term_id);


--
-- TOC entry 2203 (class 2606 OID 62270)
-- Dependencies: 198 198 2277
-- Name: PK_transport_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "PK_transport_id" PRIMARY KEY (transport_id);


--
-- TOC entry 2211 (class 2606 OID 62446)
-- Dependencies: 204 204 2277
-- Name: PK_user_id; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "PK_user_id" PRIMARY KEY (user_id);


--
-- TOC entry 2197 (class 2606 OID 62179)
-- Dependencies: 192 192 2277
-- Name: U_admission_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "U_admission_number" UNIQUE (admission_number);


--
-- TOC entry 2179 (class 2606 OID 62066)
-- Dependencies: 182 182 2277
-- Name: U_class_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "U_class_name" UNIQUE (class_name);


--
-- TOC entry 2175 (class 2606 OID 62051)
-- Dependencies: 180 180 2277
-- Name: U_dept_name; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY departments
    ADD CONSTRAINT "U_dept_name" UNIQUE (dept_name);


--
-- TOC entry 2193 (class 2606 OID 62132)
-- Dependencies: 190 190 2277
-- Name: U_emp_number; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "U_emp_number" UNIQUE (emp_number);


--
-- TOC entry 2227 (class 2606 OID 62610)
-- Dependencies: 216 216 2277
-- Name: U_exam_type; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "U_exam_type" UNIQUE (exam_type);


--
-- TOC entry 2205 (class 2606 OID 62272)
-- Dependencies: 198 198 2277
-- Name: U_route; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transport_routes
    ADD CONSTRAINT "U_route" UNIQUE (route);


--
-- TOC entry 2233 (class 2606 OID 80811)
-- Dependencies: 220 220 220 220 2277
-- Name: U_student_exam_mark; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "U_student_exam_mark" UNIQUE (student_id, class_sub_exam_id, term_id);


--
-- TOC entry 2241 (class 2606 OID 70435)
-- Dependencies: 227 227 227 2277
-- Name: U_student_fee_item; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "U_student_fee_item" UNIQUE (student_id, fee_item_id);


--
-- TOC entry 2183 (class 2606 OID 70920)
-- Dependencies: 184 184 184 2277
-- Name: U_subject_by_class_cat; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "U_subject_by_class_cat" UNIQUE (subject_name, class_cat_id);


--
-- TOC entry 2189 (class 2606 OID 62117)
-- Dependencies: 188 188 188 2277
-- Name: U_term; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY terms
    ADD CONSTRAINT "U_term" UNIQUE (start_date, end_date);


--
-- TOC entry 2213 (class 2606 OID 62448)
-- Dependencies: 204 204 2277
-- Name: U_username; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT "U_username" UNIQUE (username);


--
-- TOC entry 2215 (class 2606 OID 62456)
-- Dependencies: 206 206 2277
-- Name: countries_pk; Type: CONSTRAINT; Schema: app; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY countries
    ADD CONSTRAINT countries_pk PRIMARY KEY (countries_id);


--
-- TOC entry 2080 (class 2618 OID 70591)
-- Dependencies: 223 223 200 200 200 223 223 2235 223 223 230 2277
-- Name: _RETURN; Type: RULE; Schema: app; Owner: postgres
--

CREATE RULE "_RETURN" AS ON SELECT TO invoice_balances DO INSTEAD SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payments.amount), (0)::numeric) AS total_paid, (COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) AS balance, invoices.due_date, CASE WHEN ((invoices.due_date < (now())::date) AND ((COALESCE(sum(payments.amount), (0)::numeric) - max(invoices.total_amount)) < (0)::numeric)) THEN true ELSE false END AS past_due, invoices.canceled FROM (invoices LEFT JOIN payments ON (((invoices.inv_id = payments.inv_id) AND (payments.reversed IS FALSE)))) GROUP BY invoices.student_id, invoices.inv_id;


--
-- TOC entry 2248 (class 2606 OID 62425)
-- Dependencies: 2170 182 178 2277
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY classes
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2249 (class 2606 OID 70914)
-- Dependencies: 184 2170 178 2277
-- Name: FK_class_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY subjects
    ADD CONSTRAINT "FK_class_cat_id" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2253 (class 2606 OID 62721)
-- Dependencies: 196 182 2176 2277
-- Name: FK_class_history_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2254 (class 2606 OID 62726)
-- Dependencies: 2194 192 196 2277
-- Name: FK_class_history_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_class_history
    ADD CONSTRAINT "FK_class_history_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2261 (class 2606 OID 79017)
-- Dependencies: 218 2224 216 2277
-- Name: FK_class_subect_exam_type; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subect_exam_type" FOREIGN KEY (exam_type_id) REFERENCES exam_types(exam_type_id);


--
-- TOC entry 2262 (class 2606 OID 79022)
-- Dependencies: 214 2222 218 2277
-- Name: FK_class_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subject_exams
    ADD CONSTRAINT "FK_class_subject" FOREIGN KEY (class_subject_id) REFERENCES class_subjects(class_subject_id);


--
-- TOC entry 2258 (class 2606 OID 79002)
-- Dependencies: 182 2176 214 2277
-- Name: FK_class_subject_class; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_class" FOREIGN KEY (class_id) REFERENCES classes(class_id);


--
-- TOC entry 2263 (class 2606 OID 80795)
-- Dependencies: 2228 218 220 2277
-- Name: FK_class_subject_exam; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_class_subject_exam" FOREIGN KEY (class_sub_exam_id) REFERENCES class_subject_exams(class_sub_exam_id);


--
-- TOC entry 2259 (class 2606 OID 79007)
-- Dependencies: 2180 214 184 2277
-- Name: FK_class_subject_subject; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY class_subjects
    ADD CONSTRAINT "FK_class_subject_subject" FOREIGN KEY (subject_id) REFERENCES subjects(subject_id);


--
-- TOC entry 2250 (class 2606 OID 62154)
-- Dependencies: 176 190 2168 2277
-- Name: FK_emp_cat_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_cat_id" FOREIGN KEY (emp_cat_id) REFERENCES employee_cats(emp_cat_id);


--
-- TOC entry 2251 (class 2606 OID 62159)
-- Dependencies: 190 2172 180 2277
-- Name: FK_emp_dept_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY employees
    ADD CONSTRAINT "FK_emp_dept_id" FOREIGN KEY (dept_id) REFERENCES departments(dept_id);


--
-- TOC entry 2264 (class 2606 OID 80800)
-- Dependencies: 220 192 2194 2277
-- Name: FK_exam_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2265 (class 2606 OID 80805)
-- Dependencies: 220 2186 188 2277
-- Name: FK_exam_term; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_marks
    ADD CONSTRAINT "FK_exam_term" FOREIGN KEY (term_id) REFERENCES terms(term_id);


--
-- TOC entry 2260 (class 2606 OID 70838)
-- Dependencies: 2170 216 178 2277
-- Name: FK_exam_type_class_cat; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY exam_types
    ADD CONSTRAINT "FK_exam_type_class_cat" FOREIGN KEY (class_cat_id) REFERENCES class_cats(class_cat_id);


--
-- TOC entry 2252 (class 2606 OID 70641)
-- Dependencies: 192 233 2244 2277
-- Name: FK_installment_option; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY students
    ADD CONSTRAINT "FK_installment_option" FOREIGN KEY (installment_option_id) REFERENCES installment_options(installment_id);


--
-- TOC entry 2267 (class 2606 OID 70507)
-- Dependencies: 225 227 2238 2277
-- Name: FK_inv_item_fee_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_fee_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2268 (class 2606 OID 70512)
-- Dependencies: 225 223 2234 2277
-- Name: FK_inv_item_invoice; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoice_line_items
    ADD CONSTRAINT "FK_inv_item_invoice" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2266 (class 2606 OID 70662)
-- Dependencies: 223 2194 192 2277
-- Name: FK_invoice_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY invoices
    ADD CONSTRAINT "FK_invoice_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2271 (class 2606 OID 70527)
-- Dependencies: 200 2206 229 2277
-- Name: FK_payment_fee_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_fee_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2272 (class 2606 OID 70532)
-- Dependencies: 2234 223 229 2277
-- Name: FK_payment_inv; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv" FOREIGN KEY (inv_id) REFERENCES invoices(inv_id);


--
-- TOC entry 2273 (class 2606 OID 70537)
-- Dependencies: 229 225 2236 2277
-- Name: FK_payment_inv_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_inv_items
    ADD CONSTRAINT "FK_payment_inv_item" FOREIGN KEY (inv_item_id) REFERENCES invoice_line_items(inv_item_id);


--
-- TOC entry 2274 (class 2606 OID 70810)
-- Dependencies: 227 2238 235 2277
-- Name: FK_payment_item; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_item" FOREIGN KEY (student_fee_item_id) REFERENCES student_fee_items(student_fee_item_id);


--
-- TOC entry 2275 (class 2606 OID 70815)
-- Dependencies: 235 200 2206 2277
-- Name: FK_payment_replace_item_payment; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payment_replacement_items
    ADD CONSTRAINT "FK_payment_replace_item_payment" FOREIGN KEY (payment_id) REFERENCES payments(payment_id);


--
-- TOC entry 2255 (class 2606 OID 70805)
-- Dependencies: 192 2194 200 2277
-- Name: FK_payments_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY payments
    ADD CONSTRAINT "FK_payments_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2269 (class 2606 OID 70550)
-- Dependencies: 2198 227 194 2277
-- Name: FK_student_fee_items; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items" FOREIGN KEY (fee_item_id) REFERENCES fee_items(fee_item_id);


--
-- TOC entry 2270 (class 2606 OID 70555)
-- Dependencies: 227 2194 192 2277
-- Name: FK_student_fee_items_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_fee_items
    ADD CONSTRAINT "FK_student_fee_items_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2257 (class 2606 OID 62761)
-- Dependencies: 2194 212 192 2277
-- Name: FK_student_guardian_student; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_guardians
    ADD CONSTRAINT "FK_student_guardian_student" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2256 (class 2606 OID 70191)
-- Dependencies: 208 2194 192 2277
-- Name: FK_student_id; Type: FK CONSTRAINT; Schema: app; Owner: postgres
--

ALTER TABLE ONLY student_medical_history
    ADD CONSTRAINT "FK_student_id" FOREIGN KEY (student_id) REFERENCES students(student_id);


--
-- TOC entry 2282 (class 0 OID 0)
-- Dependencies: 5
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2016-04-27 12:00:10

--
-- PostgreSQL database dump complete
--

