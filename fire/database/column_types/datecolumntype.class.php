<?php

class DateColumnType extends ColumnType
{
    
    function to_sql()
    {
        $sql = array();

        $sql[] = 'DATE';
        
        return implode(' ', $sql);
    }

}
