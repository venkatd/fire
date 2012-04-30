<?php

class TimeColumnType extends ColumnType
{
    
    function to_sql()
    {
        $sql = array();

        $sql[] = 'DATETIME';
        
        return implode(' ', $sql);
    }

}
