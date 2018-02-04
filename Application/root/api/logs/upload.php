<?php

require "../../../vendor/autoload.php";


class EP extends APIEndpoint {
    function parseLine(\Kassner\LogParser\LogParser $parser, string $line)
    {
        try {
            $data = $parser->parse($line);
            $vals = [
                "rbytes" => isset($data->responseBytes) ? intval($data->responseBytes) : null,
                "tm" => null,
                "tmts" => isset($data->requestTime) ? intval($data->requestTime) : null,
                "mt" => null,
                "rema" => isset($data->host) ? $data->host : null,
                "urlp" => null,
                "stat" => isset($data->status) ? $data->status : null
            ];

            if (isset($data->time)) {
                $d = DateTime::createFromFormat("d/M/Y:H:i:s O", $data->time);
                $vals["tm"] = $d->format("Y-m-d H:i:s");
            }
            if (isset($data->request)) {
                $xd = explode(" ", $data->request, 3);
                $vals["mt"] = $xd[0];
                $vals["urlp"] = strstr($xd[1] . "?", "?", true);
            }
            if (isset($data->requestMethod))
                $vals["mt"] = $data->requestMethod;
            if (isset($data->URL))
                $vals["urlp"] = $data->URL;

            return $vals;
        } catch (\Kassner\LogParser\FormatException $e) {
            return false;
        }
    }

    function main(array $args) : bool
    {
        if (!isset($_REQUEST["serverName"]))
            return $this->message_("`serverName` param required");
        if (!isset($_FILES["file"]))
            return $this->message_("`file` must be sent");
        if ($_FILES["file"]["type"] != "application/gzip")
            return $this->message_("expected format is `application/gzip`");
        $stm = $this->database->prepare("SELECT * FROM servers WHERE name=?");
        $stm->execute([$_REQUEST["serverName"]]);
        $serv = $stm->fetchObject(\entities\Server::class);

        $format = $_REQUEST["format"] ?: $serv->getDefaultFormat();
        if (!$format)
            return $this->message_("format must be specified either in request or in server's defaults");
        $parser = new \Kassner\LogParser\LogParser();

        $file = \entities\LogFile::Create($serv, $_REQUEST["format"]);
        if (isset($_REQUEST["comment"]))
            $file->setComment($_REQUEST["comment"]);
        $file->save();

        $fp = gzopen($_FILES["file"]["tmp_name"], "r");
        $line = fgets($fp, \config\MAX_LOG_LINE_LENGTH);
        $line_cnt = 0;
        $invalid_cnt = 0;
        $dups_cnt = 0;
        $stm = $this->database->prepare(
            "INSERT INTO logentries 
             (uploadedfrom, responsebytes, time, timetoserve, method, remoteaddress, urlpath, status) 
             VALUES (:fro, :rbytes, :tm, :tmts, :mt, :rema, :urlp, :stat)");
        while (!feof($fp) && $line_cnt < \config\MAX_LOG_LINES) {
            ++$line_cnt;
            $exec_args = $this->parseLine($parser, $line);
            $exec_args["fro"] = $file->getId();
            if ($exec_args) {
                if (!$stm->execute($exec_args))
                    ++$invalid_cnt;
            } else {
                ++$invalid_cnt;
            }
            $line = fgets($fp, \config\MAX_LOG_LINE_LENGTH);
        }

        $file->setInvalidSkipped($invalid_cnt);
        $file->setDuplicatesSkipped($dups_cnt);

        return $this->result_($file);
    }
};
$cls = new EP();
$cls();
