<?php
require "../../../vendor/autoload.php";


$cls = new class extends APIEndpoint
{
    protected function main(array $args): bool
    {
        if (!isset($_REQUEST["name"]))
            return $this->message_("`name` param is required");

        $stm = $this->database->prepare("DELETE FROM servers WHERE name=?");
        $stm->execute([$_REQUEST["name"]]);
        return $this->result_($stm->rowCount());
    }
};
$cls();