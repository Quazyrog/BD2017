<?php
require "../../../vendor/autoload.php";


$cls = new class extends APIEndpoint
{
    protected function main(array $args): bool
    {
        if (!isset($_REQUEST["name"]))
            return $this->message_("`name` param is required");

        $stm = $this->database->prepare("SELECT * FROM servers WHERE name=?");
        $stm->execute([$_REQUEST["name"]]);
        if ($stm->rowCount() != 1)
            return $this->message_("No server with such name");
        /** @var \entities\Server $serv */
        $serv = $stm->fetchObject(\entities\Server::class);

        $mod = 0;
        if (isset($_REQUEST["description"])) {
            $serv->setDescription($_REQUEST["description"]);
            ++$mod;
        }
        if (isset($_REQUEST["defaultLogFormat"])) {
            $serv->setDescription($_REQUEST["defaultLogFormat"]);
            ++$mod;
        }

        if ($mod == 0)
            $this->message_("No changes were made");
        else
            $serv->save();
        return $this->result_($serv);
    }
};
$cls();