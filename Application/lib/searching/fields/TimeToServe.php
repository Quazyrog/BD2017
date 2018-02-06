<?php

namespace searching\fields;


class TimeToServe extends NumericField
{

    public function getLHS(): string
    {
        return "LogEntries.timeToServe";
    }

    public function getName(): string
    {
        return "seconds_to_serve";
    }
}