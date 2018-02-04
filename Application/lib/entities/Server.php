<?php

namespace entities;


class Server extends Entity
{
    private $name_;
    private $description_ = null;
    private $defaultFormat_ = null;

    /**
     * Server constructor.
     * @param string $name
     */
    public function __construct(string $name = null)
    {
        if ($name == null) {
            parent::__construct(self::LOADED);
        } else {
            parent::__construct(self::CREATED);

            if ($name != trim($name))
                throw new \InvalidArgumentException("Server name must be trimmed.");
            if (strlen($name) > 64 || strlen($name) == 0)
                throw new \InvalidArgumentException("Server name has invalid length");

            $this->name_ = $name;
            $this->update_("name", $name);
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case "name":
                $this->name_ = $value;
                break;
            case "description":
                $this->description_ = $value;
                break;
            case "defaultlogformat":
                $this->defaultFormat_ = $value;
                break;
            default:
                throw new \OutOfBoundsException("unknown property `" . $name . "`");
        }
    }

    public function getName(): string
    {
        return $this->name_;
    }

    public function getDescription() : string
    {
        return $this->description_;
    }

    public function setDescription(string $description_): void
    {
        $this->description_ = $description_;
        $this->update_("description", $description_);
    }

    public function getDefaultFormat() : string
    {
        return $this->defaultFormat_;
    }

    public function setDefaultFormat(string $defaultFormat_): void
    {
        $this->defaultFormat_ = $defaultFormat_;
        $this->update_("defaultLogFormat", $defaultFormat_);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            "name" => $this->name_,
            "description" => $this->description_,
            "defaultLogFormat" => $this->defaultFormat_,
        ];
    }

    protected function tableName_() : string
    {
        return "servers";
    }

    protected  function getKey_(): array
    {
        return ["name" => $this->name_];
    }

    protected  function setKey_(array $key): void
    {
        assert($this->name_ == $key["name"]);
    }
}

