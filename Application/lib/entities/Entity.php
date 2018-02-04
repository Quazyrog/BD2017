<?php

namespace entities;

use JsonSerializable;
use PDO;
use Throwable;


abstract class Entity implements JsonSerializable
{
    public const LOADED = 0;
    public const CREATED = 1;
    public const ZOMBIE = 2;

    private static $DefaultDatabase = null;

    protected static function MakeColumnList(array $data)
    {
        return [
            "cols" => "(" . implode(", ", array_keys($data)) . ")",
            "vals" => "(" . implode(", ", array_map(function ($k) {return ":" . $k;}, array_keys($data))) . ")"
        ];
    }

    protected $updates_ = [];
    protected $state;
    private $database_ = null;


    public static function BindDefault(PDO $db)
    {
        if ($db == null)
            throw new \InvalidArgumentException();
        self::$DefaultDatabase = $db;
    }


    public function __construct(int $state = self::ZOMBIE)
    {
        $this->state = $state;
    }

    public function bind($db)
    {
        if ($db == null)
            throw new \InvalidArgumentException();
        $this->database_ = $db;
    }

    public function save()
    {
        $db = $this->databaseBound_();
        if ($db == null)
            throw new \RuntimeException("There is no binding to the database");


        $stm_str = null;
        $args = array_merge($this->updates_,$this->getKey_());
        $udata = self::MakeColumnList($this->updates_);
        $kdata = self::MakeColumnList($this->getKey_());
        switch ($this->state)
        {
            case self::LOADED:
                $stm_str = "UPDATE " . $this->tableName_() . " SET " . $udata["cols"] . " = ROW" . $udata["vals"]
                    . " WHERE " . $kdata["cols"] . " = " . $kdata["vals"];
                break;
            case self::CREATED:
                $stm_str = "INSERT INTO " . $this->tableName_() . " " . $udata["cols"]  . " VALUES " . $udata["vals"]
                    . " RETURNING " . $kdata["cols"];
                break;
            case self::ZOMBIE:
                throw new EntitySaveError("Nobody can save zombie, even you!");
        }

        $stm = $db->prepare($stm_str);
        if (!$stm || !$stm->execute($args) || $stm->rowCount() != 1) {
            throw new EntitySaveError("Unable to save entity");
        } else {
            if ($this->state == self::CREATED)
                $this->setKey_($stm->fetch(PDO::FETCH_ASSOC));
            $this->state = self::LOADED;
            $this->updates_ = [];
        }
    }

    protected function databaseBound_() : PDO
    {
        return $this->database_ ?: self::$DefaultDatabase;
    }

    protected function update_(string $property, $value)
    {
        if (!ctype_alnum($property) || !ctype_alnum($property[0]))
            throw new \InvalidArgumentException("Invalid column name: only alnums starting from letter are allowed");
        if ($this->state == self::ZOMBIE)
            throw new \RuntimeException("Cannot operate on zombie entity.");
        $this->updates_[$property] = $value;
    }

    protected abstract function tableName_() : string;

    protected abstract function getKey_() : array;
    protected abstract function setKey_(array $key) : void;
}