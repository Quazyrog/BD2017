<?php

namespace searching;

use Throwable;



class SyntaxError extends \Exception implements \JsonSerializable
{
    protected $tokenStartIndex;
    protected $token;
    protected $source;

    public function fillSourceLocation(Tokenizer $tokenizer)
    {
        $this->source = $tokenizer->getSrc();
        $this->token = $tokenizer->getToken();
        $this->tokenStartIndex = $tokenizer->getPos() - strlen($this->token);
    }

    public function jsonSerialize()
    {
        return [
            "message" => $this->getMessage(),
            "source" => $this->source,
            "wrongTokenLocation" => $this->tokenStartIndex,
            "WrongToken" => $this->token
        ];
    }
}