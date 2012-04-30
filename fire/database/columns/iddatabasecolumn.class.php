<?php

class IdDatabaseColumn extends DatabaseColumn
{

    function to_database_value($value)
    {
        return $value;
    }

    function from_database_value($value)
    {
        return intval($value);
    }

}
