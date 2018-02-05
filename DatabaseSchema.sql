--
-- PostgreSQL database dump
--

-- Dumped from database version 10.1
-- Dumped by pg_dump version 10.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: valuelisttype; Type: TYPE; Schema: public; Owner: loggit
--

CREATE TYPE valuelisttype AS ENUM (
    'INTEGER',
    'DATETIME',
    'STRING'
);


ALTER TYPE valuelisttype OWNER TO loggit;

--
-- Name: isduplicate(character varying, integer, timestamp without time zone, integer, character varying, inet, text, integer); Type: FUNCTION; Schema: public; Owner: loggit
--

CREATE FUNCTION isduplicate(serv character varying, lg_size integer, lg_time timestamp without time zone, lg_resp_time integer, lg_method character varying, lg_host inet, lg_url text, lg_status integer) RETURNS boolean
    LANGUAGE plpgsql
    AS $$
  DECLARE
    curs CURSOR FOR SELECT responseBytes, time, timeToServe, method, remoteAddress, urlPath, status
                    FROM LogEntries LEFT JOIN LogFiles L ON LogEntries.uploadedFrom = L.id
                    WHERE serverName = serv AND time = lg_time;
  BEGIN
    FOR rec IN curs LOOP
      IF ((rec.responseBytes IS NULL AND lg_size IS NULL) OR rec.responseBytes = lg_size)
          AND ((rec.timeToServe IS NULL AND lg_resp_time IS NULL) OR rec.timeToServe = lg_resp_time)
          AND ((rec.method IS NULL AND lg_method IS NULL) OR rec.method = lg_method)
          AND ((rec.remoteAddress IS NULL AND lg_host IS NULL) OR rec.remoteAddress = lg_host)
          AND ((rec.urlPath IS NULL AND lg_url IS NULL) OR rec.urlPath=lg_url)
          AND ((rec.status IS NULL AND lg_status IS NULL) OR rec.status = lg_status) THEN
        RETURN TRUE;
      END IF;
    END LOOP;
    RETURN FALSE;
  END;
$$;


ALTER FUNCTION public.isduplicate(serv character varying, lg_size integer, lg_time timestamp without time zone, lg_resp_time integer, lg_method character varying, lg_host inet, lg_url text, lg_status integer) OWNER TO loggit;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: filters; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE filters (
    name character varying(64) NOT NULL,
    querystring character varying(256) NOT NULL
);


ALTER TABLE filters OWNER TO loggit;

--
-- Name: logentries; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE logentries (
    id integer NOT NULL,
    uploadedfrom integer NOT NULL,
    responsebytes integer,
    "time" timestamp without time zone,
    timetoserve integer,
    method character varying(32),
    remoteaddress inet,
    urlpath text,
    status integer
);


ALTER TABLE logentries OWNER TO loggit;

--
-- Name: logentries_id_seq; Type: SEQUENCE; Schema: public; Owner: loggit
--

CREATE SEQUENCE logentries_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE logentries_id_seq OWNER TO loggit;

--
-- Name: logentries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: loggit
--

ALTER SEQUENCE logentries_id_seq OWNED BY logentries.id;


--
-- Name: logfiles; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE logfiles (
    id integer NOT NULL,
    servername character varying(60) NOT NULL,
    uploaddate timestamp without time zone NOT NULL,
    uploadformat character varying(64) NOT NULL,
    duplicatesskipped integer NOT NULL,
    comment text,
    invalidskipped integer DEFAULT 0 NOT NULL
);


ALTER TABLE logfiles OWNER TO loggit;

--
-- Name: logfiles_id_seq; Type: SEQUENCE; Schema: public; Owner: loggit
--

CREATE SEQUENCE logfiles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE logfiles_id_seq OWNER TO loggit;

--
-- Name: logfiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: loggit
--

ALTER SEQUENCE logfiles_id_seq OWNED BY logfiles.id;


--
-- Name: servers; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE servers (
    name character varying(64) NOT NULL,
    description text,
    defaultlogformat character varying(64)
);


ALTER TABLE servers OWNER TO loggit;

--
-- Name: valueslistentries; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE valueslistentries (
    fromlist integer NOT NULL,
    value character varying(256) NOT NULL
);


ALTER TABLE valueslistentries OWNER TO loggit;

--
-- Name: valueslists; Type: TABLE; Schema: public; Owner: loggit
--

CREATE TABLE valueslists (
    id integer NOT NULL,
    name character varying(64) NOT NULL,
    type valuelisttype DEFAULT 'STRING'::valuelisttype NOT NULL
);


ALTER TABLE valueslists OWNER TO loggit;

--
-- Name: valueslists_id_seq; Type: SEQUENCE; Schema: public; Owner: loggit
--

CREATE SEQUENCE valueslists_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE valueslists_id_seq OWNER TO loggit;

--
-- Name: valueslists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: loggit
--

ALTER SEQUENCE valueslists_id_seq OWNED BY valueslists.id;


--
-- Name: logentries id; Type: DEFAULT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logentries ALTER COLUMN id SET DEFAULT nextval('logentries_id_seq'::regclass);


--
-- Name: logfiles id; Type: DEFAULT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logfiles ALTER COLUMN id SET DEFAULT nextval('logfiles_id_seq'::regclass);


--
-- Name: valueslists id; Type: DEFAULT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY valueslists ALTER COLUMN id SET DEFAULT nextval('valueslists_id_seq'::regclass);


--
-- Name: filters filters_pkey; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY filters
    ADD CONSTRAINT filters_pkey PRIMARY KEY (name);


--
-- Name: logentries logentries_pkey; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logentries
    ADD CONSTRAINT logentries_pkey PRIMARY KEY (id);


--
-- Name: logfiles logfiles_pkey; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logfiles
    ADD CONSTRAINT logfiles_pkey PRIMARY KEY (id);


--
-- Name: servers servers_pkey; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY servers
    ADD CONSTRAINT servers_pkey PRIMARY KEY (name);


--
-- Name: valueslists valueslists_name_key; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY valueslists
    ADD CONSTRAINT valueslists_name_key UNIQUE (name);


--
-- Name: valueslists valueslists_pkey; Type: CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY valueslists
    ADD CONSTRAINT valueslists_pkey PRIMARY KEY (id);


--
-- Name: logentries_time_idx; Type: INDEX; Schema: public; Owner: loggit
--

CREATE INDEX logentries_time_idx ON logentries USING btree ("time");


--
-- Name: logentries logentries_uploadedfrom_fkey; Type: FK CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logentries
    ADD CONSTRAINT logentries_uploadedfrom_fkey FOREIGN KEY (uploadedfrom) REFERENCES logfiles(id);


--
-- Name: logfiles logfiles_servername_fkey; Type: FK CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY logfiles
    ADD CONSTRAINT logfiles_servername_fkey FOREIGN KEY (servername) REFERENCES servers(name);


--
-- Name: valueslistentries valueslistentries_fromlist_fkey; Type: FK CONSTRAINT; Schema: public; Owner: loggit
--

ALTER TABLE ONLY valueslistentries
    ADD CONSTRAINT valueslistentries_fromlist_fkey FOREIGN KEY (fromlist) REFERENCES valueslists(id);


--
-- PostgreSQL database dump complete
--

