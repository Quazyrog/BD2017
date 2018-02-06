<?php

require "../../../vendor/autoload.php";


$foo = new class extends APIEndpoint {
    protected function main(array $args): bool
    {
        $compiler = \searching\QueryCompiler::BuildDefaultCompiler($this->database);
        try {
            $stm_str = $compiler->compile($_REQUEST["query"]) . " LIMIT 50";
        } catch (\searching\SyntaxError $e) {
            $this->message_("Syntax error");
            $this->result_($e);
            return false;
        }

        $stm = $this->database->prepare($stm_str);
        $stm->execute([$_REQUEST["servername"]]);
        return $this->result_($stm->fetchAll(PDO::FETCH_ASSOC));
    }
};
$foo();