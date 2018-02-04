CREATE FUNCTION isduplicate(serv character varying, lg_size integer, lg_time timestamp without time zone, lg_resp_time integer, lg_method character varying, lg_host inet, lg_url text, lg_status integer)
  RETURNS boolean
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

