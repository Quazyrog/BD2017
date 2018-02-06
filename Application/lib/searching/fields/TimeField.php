<?php

namespace searching\fields;

use searching\SyntaxError;

require_once "Utils.php";



class TimeField extends AbstractField
{
    private $fn = null;

    public function getLHS(): string
    {
        return "LogEntries.time";
    }

    public function getName(): string
    {
        return "time";
    }

    public function prepareRHS(string $rhs): string
    {
        if (!\utils\ValidateDate($rhs))
            throw new SyntaxError("Invalid date `" . $rhs . "` (expected format is 'YYYY-mm-dd[ HH:MM:SS])");
        return $rhs;
    }


    public function compile(string $comparator, string $rhs): string
    {
        return parent::compile($comparator, "'" . $rhs . "'");
    }

    public function getStoreType(): int
    {
        return self::VALUE_STORE_TYPE_DATETIME;
    }

    public function getStoredConversionString(): string
    {
        return "TIMESTAMP";
    }

    public function applyFunction_($function_name)
    {
        $this->fn = $function_name;
    }

    protected function selectString_(bool $aggreg)
    {
        if ($aggreg)
            return "MIN(" . $this->getLHS() . ") || ' - ' || MAX(" . $this->getLHS() . ")";
        if (!$this->fn)
            return $this->getLHS();
        switch ($this->fn) {
            case "trunc_minute":
                return "date_trunc('minute'," . $this->getLHS() . ")";
            case "trunc_hour":
                return "date_trunc('hour'," . $this->getLHS() . ")";
            case "trunc_day":
                return "date_trunc('day'," . $this->getLHS() . ")";
            case "trunc_month":
                return "date_trunc('month'," . $this->getLHS() . ")";
            default :
                throw new SyntaxError("Function `" . $this->fn . "` cannot be applied to field" . "`"
                    . $this->getName() . "`");
        }
    }
}