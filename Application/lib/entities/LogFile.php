<?php

namespace entities;


class LogFile extends Entity
{
    private $id_;
    private $server_;
    private $uploadDate_;
    private $uploadFormat_;
    private $duplicatesSkipped_;
    private $invalidSkipped_ = 0;
    private $comment_;


    public static function Create(Server $server, string $format=null, int $duplicates=0,
                                  string $comment=null) : LogFile
    {
        $format = $format ?: $server->getDefaultFormat();
        if (!$format)
            throw new \InvalidArgumentException("Format not given and server has no default");
        $entity = new LogFile();
        $entity->state = self::CREATED;
        $entity->server_ = $server;
        $entity->uploadFormat_ = $format;
        $entity->uploadDate_ = new \DateTime("now");

        $entity->update_("serverName", $server->getName());
        $entity->update_("uploadFormat", $format);
        $entity->update_("uploadDate", $entity->uploadDate_->format("Y-m-d H:i:s.u"));
        $entity->setDuplicatesSkipped($duplicates);
        if ($comment)
            $entity->setComment($comment);
        return $entity;
    }


    public function __construct()
    {
        parent::__construct(self::ZOMBIE);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "id":
                $id = intval($value);
                break;
            case "servername":
                $stm = $this->databaseBound_()->prepare("SELECT * FROM servers WHERE name=?");
                $stm->execute([$value]);
                $this->server_ = $stm->fetchObject(Server::class);
                break;
            case "uploaddate":
                $this->uploadDate_ = \DateTime::createFromFormat("Y-m-d H:i:s", $value);
                break;
            case "uploadformat":
                $this->uploadFormat_ = $value;
                break;
            case "duplicatesskipped":
                $this->duplicatesSkipped_ = intval($value);
                break;
            case "comment":
                $this->comment_ = $value;
                break;
            case "invalidskipped":
                $this->invalidSkipped_ = intval($value);
                break;
        }

        if (isset($this->id_) && isset($this->server_) && isset($this->uploadDate_) && isset($this->uploadFormat_)
            && isset($this->duplicatesSkipped_) && isset($this->invalidSkipped_))
            $this->state = self::LOADED;
    }

    public function getId()
    {
        return $this->id_;
    }

    public function getServer() : Server
    {
        return $this->server_;
    }

    public function getServerName() : string
    {
        return $this->server_->getName();
    }

    public function getUploadDate() : \DateTime
    {
        return $this->uploadDate_;
    }

    public function getUploadFormat() : string
    {
        return $this->uploadFormat_;
    }

    public function getDuplicatesSkipped() : int
    {
        return $this->duplicatesSkipped_;
    }

    public function setDuplicatesSkipped(int $duplicates_skipped): void
    {
        $this->duplicatesSkipped_ = $duplicates_skipped;
        $this->update_("duplicatesSkipped", $duplicates_skipped);
    }

    public function getInvalidSkipped(): int
    {
        return $this->invalidSkipped_;
    }

    public function setInvalidSkipped(int $invalid_skipped): void
    {
        $this->invalidSkipped_ = $invalid_skipped;
    }

    public function getComment() : string
    {
        return $this->comment_;
    }

    public function setComment(string $comment): void
    {
        $this->comment_ = $comment;
        $this->update_("comment", $comment);
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $local_date = clone $this->uploadDate_;
        $local_date->setTimezone(new \DateTimeZone(\config\TIMEZONE_NAME));
        return [
            "id" => $this->id_,
            "serverName" => $this->getServerName(),
            "uploadDate" => $local_date->format("Y-m-d H:i:s"),
            "uploadFormat" => $this->uploadFormat_,
            "duplicatesSkipped" => $this->duplicatesSkipped_,
            "invalidSkipped" => $this->invalidSkipped_,
            "comment" => $this->comment_
        ];
    }

    protected function tableName_(): string
    {
        return "LogFiles";
    }

    protected function getKey_(): array
    {
        return ["id" => $this->id_];
    }

    protected function setKey_(array $key): void
    {
        $this->id_ = intval($key["id"]);
    }
}