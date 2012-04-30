<?php

class TextDatabaseColumn extends DatabaseColumn
{

    function from_database_value($value)
    {
        return $value;
    }

    function to_database_value($value)
    {
        return strval($value);
    }

}