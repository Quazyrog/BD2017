<?php

namespace searching\fields;


class StatusField extends NumericField
{

    public function getLHS(): string
    {
        return "LogEntries.status";
    }

    public function getName(): string
    {
        return "status";
    }

    protected function selectString_(bool $aggreg)
    {
        if ($aggreg)
            return false;
        return $this->getLHS();
    }
}