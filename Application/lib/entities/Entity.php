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

    private static $defaultDatabase = null;

    private $updates_ = [];
    private $database_ = null;
    private $state;


    public static function BindDefault(PDO $db)
    {
        if ($db == null)
            throw new \InvalidArgumentException();
        self::$defaultDatabase = $db;
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

        $cols = implode(", ", array_keys($this->updates_));
        $vals = implode(", ", array_map(function ($k) {return ":" . $k;}, array_keys($this->updates_)));
        $stm_str = null;
        $args = null;
        switch ($this->state)
        {
            case self::LOADED:
                $key = $this->getKey_();
                $key_cols = implode(", ", array_keys($key));
                $key_vals = implode(", ", array_map(function ($k) {return ":" . $k;}, array_keys($key)));
                $stm_str = "UPDATE " . $this->tableName_() . " SET (" . $cols . ")" . " = ROW(" . $vals . ")"
                    . " WHERE (" . $key_cols . ") = (" . $key_vals . ")";
                $args = array_merge($this->updates_, $key);
                break;
            case self::CREATED:
                $args = $this->updates_;
                $stm_str = "INSERT INTO " . $this->tableName_() . " (" . $cols . ")" . " VALUES (" . $vals . ")";
                break;
            case self::ZOMBIE:
                throw new EntitySaveError("Nobody can save zombie, even you!");
        }

        $stm = $db->prepare($stm_str);
            error_log($stm_str);
        if (!$stm || !$stm->execute($args) || $stm->rowCount() != 1) {
            throw new EntitySaveError("Unable to save entity");
        } else {
            $this->updates_ = [];
        }
    }

    protected function databaseBound_() : PDO
    {
        return $this->database_ ?: self::$defaultDatabase;
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
}