<?php

namespace searching\fields;


use searching\SyntaxError;

class RemoteAddressField extends AbstractField
{
    private $funmask;

    public function getLHS(): string
    {
        return "LogEntries.remoteAddress";
    }

    public function getName(): string
    {
        return "address";
    }

    public function getStoreType(): string
    {
        return self::VALUE_STORE_TYPE_IP_ADDRESS;
    }

    public function getStoredConversionString(): string
    {
        return "INET";
    }

    public function compile(string $comparator, string $rhs): string
    {
        if ($comparator == "@")
            return $this->getLHS() . "<<=" . $rhs;
        return parent::compile($comparator, $rhs);
    }

    public function prepareRHS(string $rhs): string
    {
        $pos = strpos($rhs, "/");
        if ($pos === false) {
            if (filter_var($rhs, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
                return "'" . $rhs . "'";
            else
                throw new SyntaxError("Invalid IPv4 address `" . $rhs . "`");
        }

        $addr = substr($rhs, 0, $pos);
        $suf = substr($rhs, $pos + 1, 2);
        if (!$suf || ctype_digit($suf) || intval($addr) > 32)
            throw new SyntaxError("Invalid IPv4 range `" . $rhs . "`");
        if (!filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            throw new SyntaxError("Invalid IPv4 range `" . $rhs . "`");
        return "'" . $rhs . "'";
    }

    public function applyFunction_($function_name)
    {
        if (!$function_name) {
            $this->funmask = null;
            return;
        }
        if (!preg_match("/^mask[0-9]{2}/", $function_name))
            throw new SyntaxError("Function  `" . $function_name . "` is invalid for `address`");
        $len = intval(substr($function_name, 4, 2));
        $mask = 0;
        for (; $len > 0; --$len)
            $mask = ($mask << 1) | 1;
        $smask = "";
        for ($i = 0; $i <= 24; $i += 8)
            $smask .= (($mask >> $i) & 255) . ($i < 24 ? "." : "");
        $this->funmask = $smask;
    }

    protected function selectString_(bool $aggreg)
    {
        if ($aggreg)
            return false;
        if ($this->funmask)
            return $this->getLHS() . "&'" . $this->funmask . "'";
        return $this->getLHS();
    }
}