<?php

use searching\fields\AbstractField;

require "../../../vendor/autoload.php";


$foo = new class extends APIEndpoint {
    protected function main(array $args): bool
    {
        if (!isset($_REQUEST["name"]))
            return $this->message_("`name` must be set");
        if (!isset($_FILES["file"]))
            return $this->message_("`file` must be sent");
        if ($_FILES["file"]["type"] != "text/plain")
            return $this->message_("expected format is `text/plain`");

        $list = new \entities\ValuesList($_REQUEST["name"], $_REQUEST["type"]);
        try {
            $list->save();
        } catch (\entities\EntitySaveError $e) {
            return $this->message_("List with this name already exists");
        }


        $added = 0;
        $read = 1;

        $fp = fopen($_FILES["file"]["tmp_name"], "r");
        $line = trim(fgets($fp));
        $this->database->beginTransaction();
        $stm = $this->database->prepare("INSERT INTO ValuesListEntries VALUES (?, ?) ON CONFLICT DO NOTHING");
        while (!feof($fp)) {
            if ($read % \config\LOG_COMMIT_INTERVAL == 0) {
                $this->database->commit();
                $this->database->beginTransaction();
            }
            if (AbstractField::ValidateListEntry($line, $list->getType()))
                $stm->execute([$list->getId(), $line]);
            $line = trim(fgets($fp));
            ++$read;
        }
        $this->database->commit();
        fclose($fp);

        $stm = $this->database->prepare("SELECT COUNT(*) FROM ValuesListEntries WHERE fromlist=?");
        $stm->execute([$list->getId()]);
        $added = intval($stm->fetch()[0]);
        return $this->result_(["created" => $list, "added" => $added, "read" => $read]);
    }
};
$foo();