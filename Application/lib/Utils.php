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

