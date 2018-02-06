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

    protected $appliedFunction_ = null;
    private  $isAggregationField_ = false;

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

    public function selectString(bool $aggreg)
    {
        if ($this->appliedFunction_)
            throw new \RuntimeException("Nope");
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
}