<?php

namespace searching\fields;

use searching\SyntaxError;


abstract class NumericField extends AbstractField
{
    function getStoreType(): string
    {
        return self::VALUE_STORE_TYPE_INTEGER;
    }

    public function getStoredConversionString(): string
    {
        return "INT";
    }

    protected function prepareRHS_(string $rhs): string
    {
        if (!ctype_digit($rhs))
            throw new SyntaxError("Integral number expected, got `" . $rhs . "'");
        return $rhs;
    }

    protected function selectString_(bool $aggreg)
    {
        if ($aggreg)
            return "SUM(" . $this->getLHS() . ")";
        return $this->getLHS();
    }
}