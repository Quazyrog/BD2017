<?php
require "../../../vendor/autoload.php";
require "APIEndpoint.php";


use \entities\Server;


$cls = new class extends APIEndpoint {
    function main(array $args) : bool
    {
        if (isset($args["name"])) {
            $stm = $this->database->prepare("SELECT * FROM servers WHERE name = ?");
            $stm->execute([$args["name"]]);

            if ($stm->rowCount() != 1)
                $this->message_("Cannot retrieve object with given name");
            else
                return $this->result_($stm->fetchObject(Server::class));

        }

        if (isset($args["page"])) {
            if (!is_numeric($args["page"]))
                $this->message_("Page must be numeric");
            $pg = intval($args["page"]);
        } else {
            $pg = 0;
        }

        $stm = $this->database->prepare("SELECT * FROM servers LIMIT " . \config\FETCH_PAGE_SIZE
            . " OFFSET " . $pg * \config\FETCH_PAGE_SIZE);
        $stm->execute([]);

        return $this->result_($stm->fetchAll(PDO::FETCH_CLASS, Server::class));
    }
};
$cls();
