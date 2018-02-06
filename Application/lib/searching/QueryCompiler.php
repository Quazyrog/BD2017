<?php

namespace searching;

use searching\fields\AbstractField;


class QueryCompiler
{
    private $fields_ = [];

    public static function BuildDefaultCompiler(\PDO $db)
    {
        $c = new QueryCompiler();
        $c->registerField(new fields\ResponseBytesField($db));
        $c->registerField(new fields\TimeField($db));
        $c->registerField(new fields\MethodField($db));
        $c->registerField(new fields\StatusField($db));
        $c->registerField(new fields\TimeToServe($db));
        $c->registerField(new fields\URLPathField($db));
        $c->registerField(new fields\RemoteAddressField($db));
        return $c;
    }

    public function registerField(AbstractField $field) : void
    {
        $this->fields_[$field->getName()] = $field;
    }

    /**
     * @param string $query
     * @return Query
     * @throws SyntaxError
     */
    public function compile(string $query) : Query
    {
        $tokenizer = new Tokenizer($query);
        $tokenizer->next();
        $query = new Query();
        foreach ($this->fields_ as $f) {
            $f->setIsAggregationField(false);
            $f->applyFunction(null);
        }

        $query->setFilter($this->filterPartCompile_($tokenizer));
        $query->setGrouping($this->aggregatePartCompile_($tokenizer));

        if (!$tokenizer->isExhausted())
            $tokenizer->raiseSyntaxError("expected and of input");

        $shown_fields = [];
        foreach ($this->fields_ as $f) {
            $fstr = $f->selectString($query->getGrouping());
            if ($fstr)
                $shown_fields[$f->getDescription()] = $fstr;
        }
        $query->setShownFields($shown_fields);

        return $query;
    }


    /**
     * @param Tokenizer $tokenizer
     * @return string
     * @throws SyntaxError
     */
    protected function aggregatePartCompile_(Tokenizer $tokenizer) : string
    {
        if ($tokenizer->isExhausted())
            return "";

        if (!$tokenizer->matchAny(["aggregate"]))
            $tokenizer->raiseSyntaxError("`aggregate` expected");
        $grouping = [];
        foreach ($this->precompileFieldsList_($tokenizer) as $fi => $fu) {
            $field = $this->fields_[$fi];
            $field->applyFunction($fu);
            $field->setIsAggregationField(true);
            $grouping[] = $field->selectString(false);
        }

        return implode(",", $grouping);
    }

    /**
     * @param Tokenizer $tokenizer
     * @return string
     * @throws SyntaxError
     */
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
                $tokenizer->next();
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

    /**
     * @param Tokenizer $tokenizer
     * @return mixed
     * @throws SyntaxError
     */
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

    protected function precompileFieldsList_(Tokenizer $tokenizer)
    {
        $loop = true;
        $result = [];

        while ($loop) {
            if ($tokenizer->getTokenType() != Tokenizer::TT_LHS)
                $tokenizer->raiseSyntaxError("expected field or function name");
            $field = $tokenizer->getToken();
            $func = null;

            $tokenizer->next();
            switch ($tokenizer->getTokenType()) {
                case Tokenizer::TT_COMMA:
                    break;
                case Tokenizer::TT_LPARENTHESIS:
                    $tokenizer->next();
                    if ($tokenizer->getTokenType() != Tokenizer::TT_LHS)
                        $tokenizer->raiseSyntaxError("expected field name");
                    $func = $field;
                    $field = $tokenizer->getToken();
                    $tokenizer->next();
                    $tokenizer->matchAny([")"]);
                    break;
                default:
                    $loop = false;
            }
            $loop &= !$tokenizer->isExhausted();

            if (!isset($this->fields_[$field]))
                $tokenizer->raiseSyntaxError("Unknown field name `" . $field . "`");
            $result[$field] = $func;
        }

        return $result;
    }
}