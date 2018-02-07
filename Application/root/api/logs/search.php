<?php

require "../../../vendor/autoload.php";


$foo = new class extends APIEndpoint {
    protected function main(array $args): bool
    {
        if (!isset($args["servername"]))
            return $this->message_("`servername` must be set");

        $stm = $this->database->prepare("SELECT * FROM servers WHERE name=?");
        $stm->execute([$args["servername"]]);
        if ($stm->rowCount() != 1)
            return $this->message_("Cannot retrieve server with given name");
        $server = $stm->fetchObject(\entities\Server::class);

        $page = 0;
        if (isset($args["page"])) {
            if (!is_numeric($args["page"]))
            $this->message_("Page must be numeric");
            $page = intval($args["page"]);
        }

        $compiler = \searching\QueryCompiler::BuildDefaultCompiler($this->database);
        try {
            $stm_str = $compiler->compile($_REQUEST["query"]) . " LIMIT " . \config\FETCH_PAGE_SIZE
                . " OFFSET " . $page * \config\FETCH_PAGE_SIZE;
        } catch (\searching\SyntaxError $e) {
            $this->message_("Syntax error");
            $this->result_($e);
            return false;
        }

        $stm = $this->database->prepare($stm_str);
        $stm->execute([$server->getName()]);
        return $this->result_($stm->fetchAll(PDO::FETCH_ASSOC));
    }
};
$foo();