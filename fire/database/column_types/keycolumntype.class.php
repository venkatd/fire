<?php

class KeyColumnType extends ColumnType
{
    
    function to_sql()
    {
        $length = $this->length();
        return "VARCHAR($length) PRIMARY KEY NOT NULL";
    }

    function length()
    {
        return isset($this->options['length']) ? $this->options['length'] : 255;
    }

}
