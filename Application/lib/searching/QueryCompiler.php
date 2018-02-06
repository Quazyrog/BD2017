<?php

namespace searching;

use entities\Server;
use searching\fields\AbstractField;
use searching\fields\ResponseBytesField;
use searching\fields\TimeField;


class QueryCompiler
{
    private $fields_ = [];

    public static function BuildDefaultCompiler()
    {
        $c = new QueryCompiler();
        $c->registerField(new ResponseBytesField());
        $c->registerField(new TimeField());
        return $c;
    }

    public function registerField(AbstractField $field) : void
    {
        $this->fields_[$field->getName()] = $field;
    }

    public function compile(string $query) : string
    {
        $tokenizer = new Tokenizer($query);
        $tokenizer->next();

        $filter = $this->filterPartCompile_($tokenizer);

        $shown_fields = [];
        foreach ($this->fields_ as $f) {
            $fstr = $f->selectString(false);
            if ($fstr)
                $shown_fields[] = $fstr;
        }

        return "SELECT " . implode(",", $shown_fields) . " FROM LogEntries"
            . " LEFT JOIN LogFiles ON LogEntries.uploadedFrom = LogFiles.id"
            . " WHERE LogFiles.serverName=? AND (" . $filter . ")";
    }

    protected function filterPartCompile_(Tokenizer $tokenizer) : string
    {
        $compiled = "";
        $loop = true;
        while ($loop) {
            $loop = false;

            if ($tokenizer->getTokenType() == Tokenizer::TT_LHS) {
                $compiled .= $this->compileFilterTerm_($tokenizer);
            } else if ($tokenizer->getTokenType() == Tokenizer::TT_LPARENTHESIS) {
                $tokenizer->next();
                $compiled .= "(" . $this->filterPartCompile_($tokenizer) . ")";
                if ($tokenizer->getTokenType() != Tokenizer::TT_RPARENTHESIS)
                    $tokenizer->raiseSyntaxError("expected `)`");
            } else {
                $tokenizer->raiseSyntaxError("expected valid filter term");
            }

            $op = $tokenizer->matchAny(["and", "or"]);
            if ($op) {
                $compiled .= " " . strtoupper($op) . " ";
                $loop = true;
            }
        };
        return $compiled;
    }

    protected function compileFilterTerm_(Tokenizer $tokenizer)
    {
        $lhs = $tokenizer->getToken();
        if (!isset($this->fields_[$lhs]))
            $tokenizer->raiseSyntaxError("unknow field");
        $field = $this->fields_[$lhs];

        $tokenizer->next();
        if ($tokenizer->getTokenType() != Tokenizer::TT_COMPARATOR)
            $tokenizer->raiseSyntaxError("expected valid comparator expression");
        $cmp = $tokenizer->getToken();

        $tokenizer->next();
        if ($tokenizer->getTokenType() != Tokenizer::TT_RHS)
            $tokenizer->raiseSyntaxError("expected valid rhs expression");
        $rhs = $field->prepareRHS($tokenizer->getToken());

        $tokenizer->next();
        return $field->compile($cmp, $rhs);
    }
}