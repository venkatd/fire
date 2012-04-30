<?php

class TextColumnType extends ColumnType
{

    function to_sql()
    {
        $sql = array();

        $sql[] = 'TEXT';
        
        return implode(' ', $sql);
    }
    
}
