<?php

class BooleanDatabaseColumn extends DatabaseColumn
{

    function to_database_value($value)
    {
        return $value ? 1 : 0;
    }

    function from_database_value($value)
    {
        return $value == '0' ? false : true;
    }

}
