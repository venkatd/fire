<?php

class DateDatabaseColumn extends DatabaseColumn
{

    function from_database_value($value)
    {
        return new XDateTime($value, new DateTimeZone('UTC'));
    }

    function to_database_value($value)
    {
        return $value ? $value->format('Y-m-d') : null;
    }

}
