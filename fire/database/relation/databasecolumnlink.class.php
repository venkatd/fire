<?php

class DatabaseColumnLink extends DatabaseLink
{
    /* @var $left_table DatabaseTable */
    public $left_table;

    /* @var $right_column DatabaseColumn */
    public $right_column;

    function __construct(DatabaseTable $left_table, DatabaseColumn $right_column)
    {
        $this->left_table = $left_table;
        $this->right_column = $right_column;
    }

    function __toString()
    {
        return $this->left_table->name() . '.' . $this->right_column->name();
    }

}
