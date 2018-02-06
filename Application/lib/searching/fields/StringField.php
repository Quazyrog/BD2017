<?php

namespace searching\fields;


abstract class StringField extends AbstractField
{
    public function getStoreType(): string
    {
        return self::VALUE_STORE_TYPE_STRING;
    }

    public function getStoredConversionString(): string
    {
        return "";
    }

    protected function prepareRHS_(string $rhs): string
    {
        return $this->database_->quote($rhs);
    }
}