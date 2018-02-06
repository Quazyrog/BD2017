<?php

namespace searching;


class Query
{
    private $shownFields_;
    private $orderField_ = null;
    private $filter_;
    private $grouping_= null;

    public function __toString() : string
    {
        $str = "SELECT " . implode(",", $this->shownFields_) . " FROM LogEntries"
            . " LEFT JOIN LogFiles ON LogEntries.uploadedFrom = LogFiles.id"
            . " WHERE LogFiles.serverName=? AND (" . $this->filter_ . ")";
        if ($this->grouping_)
            $str .= " GROUP BY " . $this->grouping_;
        if ($this->orderField_)
            $str .= " ORDER BY " . $this->orderField_;
        return $str;
    }

    public function getShownFields() : array
    {
        return $this->shownFields_;
    }

    public function setShownFields(array $shown_fields): void
    {
        $this->shownFields_ = $shown_fields;
    }

    public function getOrderField() : string
    {
        return $this->orderField_;
    }

    public function setOrderField(string $order_field) : void
    {
        if (!in_array($order_field, $this->shownFields_))
            throw new \InvalidArgumentException("`" . $order_field . "` is not valid field");
        $this->orderField_ = $order_field;
    }

    public function getFilter() : string
    {
        return $this->filter_;
    }

    public function setFilter(string $filter) : void
    {
        $this->filter_ = $filter;
    }

    public function getGrouping() : string
    {
        return $this->grouping_;
    }

    public function setGrouping(string $grouping) : void
    {
        $this->grouping_ = $grouping;
    }
}