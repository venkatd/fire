<?php

class BooleanColumnType extends ColumnType
{
    function to_sql()
    {
        return 'BOOLEAN';
    }
}
