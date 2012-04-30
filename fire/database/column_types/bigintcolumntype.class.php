<?php

class BigIntColumnType extends ColumnType
{

    function to_sql()
    {
        $sql = array();

        $sql[] = 'BIGINT';
        if ( ! isset($this->options['unsigned']) || $this->options['unsigned'] == true) {
            $sql[] = 'UNSIGNED';
        }

        return implode(' ', $sql);
    }
    
}
