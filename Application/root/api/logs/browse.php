<?php

require "../../../vendor/autoload.php";
require "APIEndpoint.php";
require "Utils.php";

$foo = new class extends APIEndpoint {
    protected function main(array $args): bool
    {
        if (!isset($_REQUEST["servername"]))
            return $this->message_("`servername` param required");
        $stm = $this->database->prepare("SELECT * FROM servers WHERE name=?");
        $stm->execute([$_REQUEST["servername"]]);
        $server = $stm->fetchObject(\entities\Server::class);
        if (!$server)
            return $this->message_("No such server" );

        $page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;
        $stm = $this->database->prepare(
            "SELECT logentries.* FROM logentries LEFT JOIN logfiles l ON logentries.uploadedfrom = l.id
             WHERE servername=? LIMIT " . \config\FETCH_PAGE_SIZE . " OFFSET " . $page * \config\FETCH_PAGE_SIZE);
        $stm->execute([$server->getName()]);

        $result_cols = ["responseBytes", "time", "timeToServe", "method", "remoteAddress", "urlPath", "status"];
        return $this->result_(array_map(\utils\MapFromLowercase($result_cols), $stm->fetchAll(PDO::FETCH_ASSOC)));
    }
};
$foo();