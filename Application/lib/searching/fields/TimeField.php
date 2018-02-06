<?php

namespace searching\fields;

use searching\SyntaxError;

require_once "Utils.php";



class TimeField extends AbstractField
{
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
}