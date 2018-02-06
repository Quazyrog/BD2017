<?php

namespace searching;


class Tokenizer
{
    public const COMPARATOR_CHARS = "!@%^&*+=<>:?";
    public const RHS_TERMINATION_CHARS = "()";

    public const TT_ERROR = -1;
    public const TT_OPERATOR = 0;
    public const TT_LPARENTHESIS = 1;
    public const TT_RPARENTHESIS = 2;
    public const TT_LHS = 3;
    public const TT_COMPARATOR = 4;
    public const TT_RHS = 5;
    public const TT_GROUP_BY = 6;
    public const TT_COMMA = 7;

    protected $src_;
    protected $pos_ = 0;
    protected $tokenType_ = self::TT_LPARENTHESIS;
    protected $token_ = null;

    public function __construct(string $src)
    {
        $this->src_ = $src;
    }

    public function getSrc() : string
    {
        return $this->src_;
    }

    public function getPos() : int
    {
        return $this->pos_;
    }

    public function getTokenType() : int
    {
        return $this->tokenType_;
    }

    public function getToken() : string
    {
        return $this->token_;
    }

    public function isExhausted() : bool
    {
        if ($this->pos_ + 1 >= strlen($this->src_)) {
            $this->tokenType_ = self::TT_ERROR;
            $this->token_ = false;
            return true;
        }
        return false;
    }

    /**
     * Throw syntax error supplied with current tokenizer state.
     * @param string $msg additional message
     * @throws SyntaxError
     */
    public function raiseSyntaxError(string $msg)
    {
        $e = new SyntaxError($msg);
        $e->fillSourceLocation($this);
        throw $e;
    }


    public function matchAny($tokens)
    {
        foreach ($tokens as $t) {
            if ($this->token_ == $t) {
                $this->next();
                return $t;
            }
        }
        return false;
    }

    public function next() : string
    {
        $this->skipSpace_();
        if ($this->isExhausted())
            return false;

        switch ($this->tokenType_) {
            case self::TT_COMMA:
            case self::TT_LPARENTHESIS:
                $this->token_ = $this->getLParenthesis_() ?: $this->getLHS_();
                break;
            case self::TT_LHS:
                $this->token_ = $this->getComparator_() ?: $this->getExpected(self::TT_COMMA, ",")
                    ?: $this->getLParenthesis_();
                break;
            case self::TT_COMPARATOR:
                $this->token_= $this->getListName_() ?: $this->getRHS_();
                break;
            case self::TT_RHS:
            case self::TT_RPARENTHESIS:
                $this->token_ = $this->getRParenthesis_() ?: $this->getOperator_()
                    ?: $this->getExpected(self::TT_GROUP_BY, "aggregate");
                break;
            case self::TT_OPERATOR:
                $this->token_ = $this->getLParenthesis_() ?: $this->getLHS_();
                break;
        }

        if (!$this->token_)
            $this->tokenType_ = self::TT_ERROR;
        return $this->token_;
    }


    protected function skipSpace_() : void
    {
        while ($this->pos_ < strlen($this->src_) && ctype_space($this->src_[$this->pos_]))
            ++$this->pos_;
    }


    protected function getListName_()
    {
        $npos = $this->pos_;
        $c =$this->src_[$npos];
        $next_chr = function () use (&$npos, &$c)
        {
            if ($npos + 1 < strlen($this->src_)) {
                $c = $this->src_[++$npos];
                return true;
            }
            return false;
        };

        $yield = "$";
        if ($c != "$")
            return false;
        $next_chr();
        if ($c == "{") {
            $next_chr();
            while ($c && $c != "}") {
                $yield .= $c;
                $next_chr();
            }
            if ($c != "}")
                $this->raiseSyntaxError("expected `}`");
            $next_chr();
            $this->pos_ = $npos;
        } else {
            $this->pos_ = $npos;
            $yield .= $this->getLHS_();
        }

        if ($yield) {
            $this->tokenType_ = self::TT_RHS;
            return $yield;
        }
        return false;
    }

    protected function getLParenthesis_()
    {
        return $this->getExpected(self::TT_LPARENTHESIS, "(");
    }

    protected function getLHS_()
    {
        $predicate = function (string $c) : bool
        {
            return ctype_alnum($c) || $c == "_";
        };
        return $this->getWithPredicate_(self::TT_LHS, $predicate);
    }

    protected function getComparator_()
    {
        $predicate = function (string $c) : bool
        {
            return strpos(self::COMPARATOR_CHARS, $c) !== false;
        };
        return $this->getWithPredicate_(self::TT_COMPARATOR, $predicate);
    }

    protected function getRHS_()
    {
        $next_chr = function () use (&$n_pos, &$c)
        {
            if ($n_pos + 1 < strlen($this->src_)) {
                $c = $this->src_[++$n_pos];
                return true;
            }
            return false;
        };

        $n_pos = $this->pos_;
        $c = $this->src_[$n_pos];
        $yield = "";
        $quote = false;
        while ($quote || (!ctype_space($c) && strpos(self::RHS_TERMINATION_CHARS, $c) === false)) {
            switch ($c) {
                case '"':
                    $quote = !$quote;
                    break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case "\\":
                    if (!$next_chr())
                        throw new SyntaxError("Escape without next character at " . $n_pos);
                default:
                    $yield .= $c;
                    break;
            }
            if (!$next_chr())
                break;
        }

        if ($yield) {
            if ($quote)
                throw new SyntaxError("Quote string not ended at pos " . $n_pos);
            $this->tokenType_ = self::TT_RHS;
            $this->pos_ = $n_pos;
            return $yield;
        }
        return false;
    }

    protected function getRParenthesis_()
    {
        return $this->getExpected(self::TT_RPARENTHESIS, ")");
    }

    protected function getOperator_()
    {
        return $this->getExpected(self::TT_OPERATOR, "or") ?: $this->getExpected(self::TT_OPERATOR, "and")
            ?: $this->getExpected(self::TT_OPERATOR, "aggregate");
    }


    protected function getExpected(int $tt, string $expected)
    {
        if (substr($this->src_, $this->pos_, strlen($expected)) == $expected) {
            $this->tokenType_ = $tt;
            $this->pos_ += strlen($expected);
            return $expected;
        }
        return false;
    }

    protected function getWithPredicate_(int $tt, callable $pred)
    {
        $n_pos = $this->pos_;
        $c = $this->src_[$n_pos];
        $yield = "";
        while ($pred($c)) {
            $yield .= $c;
            if ($n_pos + 1 < strlen($this->src_))
                $c = $this->src_[++$n_pos];
            else
                break;
        }

        if ($yield) {
            $this->tokenType_ = $tt;
            $this->pos_ = $n_pos;
            return $yield;
        }
        return false;
    }
}