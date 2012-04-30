<?php

class StringColumnType extends ColumnType
{

    function to_sql()
    {
        $sql = array();

        $sql[] = 'VARCHAR(' . $this->length() . ')';
        
        return implode(' ', $sql);
    }

    function length()
    {
        return isset($this->options['length']) ? $this->options['length'] : 255;
    }

}
