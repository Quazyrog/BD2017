<?php

namespace searching\fields;


class URLPathField extends StringField
{

    public function getLHS(): string
    {
        return "LogEntries.urlPath";
    }

    public function getName(): string
    {
        return "url";
    }
}