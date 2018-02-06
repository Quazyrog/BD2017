<?php

namespace searching\fields;


class MethodField extends StringField
{

    public function getLHS(): string
    {
        return"LogEntries.method";
    }

    public function getName(): string
    {
        return "method";
    }
}