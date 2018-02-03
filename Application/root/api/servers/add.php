<?php
require "../../../vendor/autoload.php";
require "APIEndpoint.php";

$cls = new class extends APIEndpoint
{
    public function main($args): bool
    {
        if (!isset($_REQUEST["name"]))
            return $this->message_("Name is not optional");

        try {
            $serv = new \entities\Server($_REQUEST["name"]);

            if (isset($_REQUEST["defaultFormat"])) {
                if (strlen($_REQUEST["defaultFormat"]) > 0)
                    $serv->setDefaultFormat($_REQUEST["defaultFormat"]);
            }

            if (isset($_REQUEST["description"])) {
                $ds = trim($_REQUEST["description"]);
                if (strlen($ds) > 0)
                    $serv->setDescription($ds);
            }

        } catch (InvalidArgumentException $e) {
            return $this->message_("Invalid input: " . $e->getMessage());
        }

        try {
            $serv->save();
        } catch (\entities\EntitySaveError $e) {
            return $this->message_($e->getMessage());
        }
        return true;
    }
};
$cls();