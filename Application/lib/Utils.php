<?php

namespace utils;

function MapFromLowercase(array $names) : callable
{
    return function (array $mapped) use ($names) {
        $result = [];
        foreach ($names as $name)
            $result[$name] = $mapped[strtolower($name)] ?: null;
        return $result;
    };
}

function ValidateDate($date)
{
    $d = \DateTime::createFromFormat("Y-m-d", $date);
    if ($d && $d->format("Y-m-d") == $date)
        return true;
    $d = \DateTime::createFromFormat("Y-m-d H:i:s", $date);
    return $d && $d->format("Y-m-d H:i:s") == $date;
}


