<?php

namespace searching\fields;

use searching\SyntaxError;


class ResponseBytesField extends NumericField
{
    function getName(): string
    {
        return "size";
    }

    function getLHS(): string
    {
        return "LogEntries.responseBytes";
    }

    function prepareRHS(string $rhs) : string
    {
        $err_msg = "Invalid right-hand side: `" . $rhs . "`";
        $spl = $this->splitRHS_($rhs);
        if (!$spl[0])
            throw new SyntaxError($err_msg);
        return strval($spl[0] * $this->getUnitMultiplicity_($spl[1]));
    }

    public function selectString(bool $aggreg)
    {
        if ($aggreg)
            return "SUM(" . $this->getLHS() . ")";
        return $this->getLHS();
    }

    protected function getUnitMultiplicity_($unit)
    {
        $err_msg = "Invalid size unit `" . $unit ."`; valid units: K, M, G, Ki, Mi, Gi";
        $base = 1000;

        switch (strlen($unit)) {
            case 0:
                return 1;
            case 1:
                break;
            case 2:
                if ($unit[1] != "i")
                    throw new SyntaxError($err_msg);
                $base = 1024;
                break;
            default:
                throw new SyntaxError($err_msg);
        }

        switch ($unit[0]) {
            case "K":
                return $base;
            case "M":
                return $base ** 2;
            case "G":
                return$base ** 3;
        }

        throw new SyntaxError($err_msg);
    }

    protected function splitRHS_($rhs)
    {
        $i = 0;
        for (; $i < strlen($rhs) && ctype_digit($rhs[$i]); ++$i) {};
        if ($i < strlen($rhs))
            return [substr($rhs, 0, $i), substr($rhs, $i)];
        return [$rhs, ""];
    }
}




