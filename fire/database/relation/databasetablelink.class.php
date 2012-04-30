<?php

class DatabaseTableLink extends DatabaseLink
{
    /* @var $left_table DatabaseTable */
    public $left_table;

    /* @var $left_column DatabaseColumn */
    public $left_column;

    /* @var $right_table DatabaseTable */
    public $right_table;

    /* @var $right_column DatabaseColumn */
    public $right_column;

    function __construct(DatabaseColumn $left_column, DatabaseColumn $right_column)
    {
        $this->left_table = $left_column->table();
        $this->left_column = $left_column;

        $this->right_table = $right_column->table();
        $this->right_column = $right_column;
    }

    /**
     * @return DatabaseTableLink
     */
    function reverse()
    {
        $link = clone $this;

        $link->left_table = $this->right_table;
        $link->left_column = $this->right_column;

        $link->right_table = $this->left_table;
        $link->right_column = $this->left_column;

        return $link;
    }

    function __toString()
    {
        $left_table_name = $this->left_table->name();
        $left_column_name = $this->left_column->name();

        $right_table_name = $this->right_table->name();
        $right_column_name = $this->right_column->name();

        return "$left_table_name ($left_column_name) -> ($right_column_name) $right_table_name";
    }

}
