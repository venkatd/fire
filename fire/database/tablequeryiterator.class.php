<?php

class TableQueryIterator implements Iterator
{

    private $table;
    private $sql;
    private $params;

    private $iterator_position;
    private $iterator_row_ids;

    function __construct(DatabaseTable $table, $sql, $params = array())
    {
        $this->table = $table;
        $this->sql = $sql;
        $this->params = $params;
    }


    function current()
    {
        $key = $this->key();
        return $key ? $this->table->row($key)
                    : null;
    }

    function key()
    {
        if (!$this->valid())
            return null;

        return $this->iterator_row_ids[$this->iterator_position];
    }

    function next()
    {
        $this->iterator_position++;
    }

    function rewind()
    {
        $query = $this->table->database()->query_statement($this->sql, $this->params);
        $query->execute();

        $this->iterator_row_ids = $query->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->iterator_position = 0;
    }

    function valid()
    {
        return $this->iterator_row_ids
               && isset($this->iterator_row_ids[$this->iterator_position]);
    }

}
