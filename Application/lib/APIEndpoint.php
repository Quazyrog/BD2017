<?php
require "config.php";


abstract class APIEndpoint
{
    protected $database;
    private $result_ = null;
    private $messages_ = [];

    public function __construct(bool $connect = true, bool $bind = true)
    {
        if ($connect) {
            $this->database = new PDO(config\database\DSN, config\database\USER, config\database\PASSWORD);
            if ($bind)
                \entities\Entity::BindDefault($this->database);
        }
    }

    final public function __invoke() : void
    {
        $res = [];
        try {
            $res["success"] = $this->main($_REQUEST);
            if (isset($this->result_))
                $res["result"] = $this->result_;
        } catch (Exception $e) {
            error_log($e);
            $this->message_($e->getMessage());
        } finally {
            if (sizeof($this->messages_) > 0)
                $res["messages"] = $this->messages_;
            echo json_encode($res);
        }
    }

    protected abstract function main(array $args) : bool;

    final protected function message_(string $msg, $ret=false)
    {
        $this->messages_[] = $msg;
        return $ret;
    }

    final protected function result_($res)
    {
        $this->result_ = $res;
        return true;
    }
}