<?php

namespace searching\fields;


use searching\SyntaxError;

abstract class AbstractField
{
    const VALUE_STORE_TYPE_NO_STORE = 0;
    const VALUE_STORE_TYPE_INTEGER = 1;
    const VALUE_STORE_TYPE_STRING = 2;
    const VALUE_STORE_TYPE_DATETIME = 3;
    const VALUE_STORE_TYPE_IP_ADDRESS = 4;

    private  $isAggregationField_ = false;
    protected $database_;

    public function __construct(\PDO $db)
    {
        $this->database_ = $db;
    }

    static public function MapListType(string $list_type_name) : int
    {
        switch ($list_type_name) {
            case "INTEGER":
                return self::VALUE_STORE_TYPE_INTEGER;
            case "STRING":
                return self::VALUE_STORE_TYPE_STRING;
            case "DATETIME":
                return self::VALUE_STORE_TYPE_DATETIME;
            case "IP_ADDRESS":
                return self::VALUE_STORE_TYPE_IP_ADDRESS;
            default:
                throw new \InvalidArgumentException("Invalid list type name `" . $list_type_name . "``");
        }
    }

    public abstract function getLHS(): string;

    public function prepareRHS(string $rhs): string
    {
        return $rhs;
    }

    public abstract function getName(): string;

    public function compile(string $comparator, string $rhs): string
    {
        $allowed_comparators = ["=", "!=", "<", ">", ">=", "<="];
        if (!in_array($comparator, $allowed_comparators))
            throw new SyntaxError("Error near `" . $this->getLHS() . $comparator . "`"
                . ": comparator is expected to be one of: " . implode(", ", $comparator));
        return $this->getLHS() . $comparator . $rhs;
    }

    public abstract function getStoreType(): int;

    public abstract function getStoredConversionString() : string;

    final public function selectString(bool $aggreg)
    {
        return $this->selectString_($aggreg && !$this->isAggregationField_);
    }

    protected function selectString_(bool $aggreg)
    {
        if (!$aggreg || $this->isAggregationField_)
            return $this->getLHS();
        return false;
    }

    public function isAggregationField(): bool
    {
        return $this->isAggregationField_;
    }

    public function setIsAggregationField(bool $is_aggregation_field): void
    {
        $this->isAggregationField_ = $is_aggregation_field;
    }

    public function applyFunction($function_name)
    {
        if ($function_name)
            throw new SyntaxError("Function `" . $function_name . "` cannot be applied to field" . "`"
                . $this->getName() . "`");
    }
}