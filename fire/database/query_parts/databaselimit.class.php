<?php

class DatabaseLimit extends QueryPart
{

    public $limit;
    public $offset;

    function __construct($limit, $offset)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    function to_sql()
    {
        $sql = "LIMIT $this->limit";

        if ($this->offset)
            $sql .= " OFFSET $this->offset";

        return $sql;
    }

}
