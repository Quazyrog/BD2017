<?php

namespace searching\fields;


use searching\SyntaxError;
use function utils\ValidateDate;

abstract class AbstractField
{
    const VALUE_STORE_TYPE_NO_STORE = "";
    const VALUE_STORE_TYPE_INTEGER = "INTEGER";
    const VALUE_STORE_TYPE_STRING = "STRING";
    const VALUE_STORE_TYPE_DATETIME = "DATETIME";
    const VALUE_STORE_TYPE_IP_ADDRESS = "IP_ADDRESS";

    private  $isAggregationField_ = false;
    protected $database_;
    protected $appliedFunctionName;

    public function __construct(\PDO $db)
    {
        $this->database_ = $db;
    }

    public static function ValidateListEntry(string $entry, string $store_type) : bool
    {
        switch ($store_type) {
            case self::VALUE_STORE_TYPE_INTEGER:
                return filter_var($entry, FILTER_VALIDATE_INT);
            case self::VALUE_STORE_TYPE_DATETIME:
                return ValidateDate($entry);
            case self::VALUE_STORE_TYPE_STRING:
                return true;
            case self::VALUE_STORE_TYPE_IP_ADDRESS:
                return filter_var($entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            default:
                throw new \InvalidArgumentException("Invalid store type for validation");
        }
    }

    public abstract function getLHS(): string;

    final public function prepareRHS(string $rhs): string
    {
        if ($rhs[0] != "$")
            return $this->prepareRHS_($rhs);
        return $rhs;
    }

    protected function prepareRHS_(string $rhs): string
    {
        return $rhs;
    }

    public abstract function getName(): string;

    public function compile(string $comparator, string $rhs): string
    {
        if ($rhs[0] == "$") {
            if ($comparator != ":")
                throw new SyntaxError("`:` is the only allowed comparator for list checks");
            $stm = $this->database_->prepare("SELECT * FROM ValuesLists WHERE name=?");
            $stm->execute([substr($rhs, 1)]);
            if ($stm->rowCount() != 1)
                throw new SyntaxError("Values list `" . $rhs . "` does not exist");
            $list = $stm->fetchObject(\entities\ValuesList::class);
            if ($list->getType() != $this->getStoreType())
                throw new SyntaxError("Values list `" . $rhs . "` and field `" . $this->getName()
                    . "` have incompatible types");

            $conv = $this->getStoredConversionString();
            if ($conv)
                $conv = "::" . $conv;
            return $this->getLHS() . " IN (SELECT value" . $conv . " FROM ValuesListEntries WHERE fromList="
                . $list->getId() . ")";
        }

        $allowed_comparators = ["=", "!=", "<", ">", ">=", "<="];
        if (!in_array($comparator, $allowed_comparators))
            throw new SyntaxError("Error near `" . $this->getLHS() . $comparator . "`"
                . ": comparator is expected to be one of: " . implode(", ", $comparator));
        return $this->getLHS() . $comparator . $rhs;
    }

    public abstract function getStoreType(): string;

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

    final public function applyFunction($function_name)
    {
        $this->applyFunction_($function_name);
        $this->appliedFunctionName = $function_name;
    }

    protected function applyFunction_($function_name)
    {
        if ($function_name)
            throw new SyntaxError("Function `" . $function_name . "` cannot be applied to field" . "`"
                . $this->getName() . "`");
    }

    final public function getDescription() : string
    {
        if ($this->appliedFunctionName)
            return $this->appliedFunctionName . "(" . $this->getName() . ")";
        return $this->getName();
    }
}