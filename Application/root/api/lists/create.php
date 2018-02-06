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
        $list->save();
        $added = 0;
        $skipped = 0;

        $fp = fopen($_FILES["file"]["tmp_name"], "r");
        $line = trim(fgets($fp));
        $stm = $this->database->prepare("INSERT INTO ValuesListEntries VALUES (?, ?)");
        $this->database->beginTransaction();
        while (!feof($fp)) {
            if ($added % \config\LOG_COMMIT_INTERVAL == 0) {
                $this->database->commit();
                $this->database->beginTransaction();
            }
            if (AbstractField::ValidateListEntry($line, $list->getType()) && $stm->execute([$list->getId(), $line]))
                ++$added;
            else
                ++$skipped;
            $line = trim(fgets($fp));
        }
        $this->database->commit();
        fclose($fp);

        return $this->result_(["created" => $list, "added" => $added, "skipped" => $skipped]);
    }
};
$foo();