<?php

namespace entities;

use \searching\fields\AbstractField;


class ValuesList extends Entity
{
    private $id_;
    private $name_;
    private $type_;
    private $length_;

    public function __construct(string $name="", string $store_type=AbstractField::VALUE_STORE_TYPE_NO_STORE)
    {
        if (!$name || $store_type == AbstractField::VALUE_STORE_TYPE_NO_STORE) {
            parent::__construct(self::ZOMBIE);
        } else {
            if ($store_type != AbstractField::VALUE_STORE_TYPE_IP_ADDRESS
                && $store_type != AbstractField::VALUE_STORE_TYPE_STRING
                && $store_type != AbstractField::VALUE_STORE_TYPE_DATETIME
                && $store_type != AbstractField::VALUE_STORE_TYPE_INTEGER)
                throw new \InvalidArgumentException("Invalid store type");
            parent::__construct(self::CREATED);
            $this->name_ = $name;
            $this->update_("name", $name);
            $this->type_ = $store_type;
            $this->update_("type", $store_type);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "id":
                $this->id_ = intval($value);
                break;
            case "name":
                $this->name_ = $value;
                break;
            case "type":
                $this->type_ = $value;
                break;
            default:
                throw new \InvalidArgumentException("Invalid property name `" . $name . "`");
        }
        if (isset($this->id_) && isset($this->name_) && isset($this->type_))
            $this->state = self::LOADED;
    }

    protected function tableName_(): string
    {
        return "ValuesLists";
    }

    protected function getKey_(): array
    {
        return ["id" => $this->id_];
    }

    protected function setKey_(array $key): void
    {
        $this->id_ = intval($key["id"]);
    }

    public function jsonSerialize()
    {
        return ["name" => $this->name_, "type" => $this->type_, "length" => $this->getLength()];
    }

    public function getId()
    {
        return $this->id_;
    }

    public function getName(): string
    {
        return $this->name_;
    }

    public function getType(): string
    {
        return $this->type_;
    }

    public function getLength() : int
    {
        if (!isset($this->length_)) {
            $stm = $this->databaseBound_()->prepare("SELECT COUNT(*) FROM valueslistentries WHERE fromlist=?");
            $stm->execute([$this->getId()]);
            $this->length_ = intval($stm->fetch()[0]);
        }
        return $this->length_;
    }
}