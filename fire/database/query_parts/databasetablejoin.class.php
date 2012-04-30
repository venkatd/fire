<?php

class DatabaseTableJoin
{
    /**
     * @var DatabaseTable
     */
    public $base_table;

    /**
     * @var DatabaseColumn
     */
    public $base_column;

    /**
     * @var DatabaseTable
     */
    public $join_table;

    /**
     * @var DatabaseColumn
     */
    public $join_column;

    function __construct(DatabaseTable $base_table, DatabaseColumn $base_column,
        DatabaseTable $join_table, DatabaseColumn $join_column)
    {
        $this->base_table = $base_table;
        $this->base_column = $base_column;

        $this->join_table = $join_table;
        $this->join_column = $join_column;
    }

    function to_sql()
    {
        $base_table_name = $this->base_table->name();
        $base_column_name = $this->base_column->name();
        $join_table_name = $this->join_table->name();
        $join_column_name = $this->join_column->name();

        return "INNER JOIN $join_table_name ON $base_table_name.$base_column_name = $join_table_name.$join_column_name";
    }

}
