<?php

class DatabaseRow
{

    private $values = array();
    private $changes = array();

    /**
     * @var DatabaseTable
     */
    private $table;

    function __construct(DatabaseTable $table, $id)
    {
        $this->table = $table;

        if ($this->table()->id_column())
            $this->values = array($this->table()->id_column()->name() => $id);
    }

    function values()
    {
        return $this->values;
    }

    function _set_values(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return DatabaseTable
     */
    function table()
    {
        return $this->table;
    }

    /**
     * @return Database
     */
    function database()
    {
        return $this->table()->database();
    }

    /**
     * @param  $name
     * @return DatabaseColumn
     */
    function column($name)
    {
        return $this->table->column($name);
    }

    function changes()
    {
        return $this->changes;
    }

    function __get($field)
    {
        if (isset($this->changes[$field])) {
            $value = $this->changes[$field];
            $converted_value = $this->column($field)->from_database_value($value);
            return $converted_value;
        }
        elseif (isset($this->values[$field])) {
            $value = $this->values[$field];
            $converted_value = $this->column($field)->from_database_value($value);
            return $converted_value;
        }
        elseif ($this->is_one_to_one_reference($field)) {
            return $this->resolve_one_to_one_reference($field);
        }
        elseif ($this->is_reference($field)) {
            return $this->resolve_reference($field);
        }
        else {
            return null;
        }
    }

    function __set($field, $value)
    {
        $converted_value = $this->column($field)->to_database_value($value);
        $this->changes[$field] = $converted_value;
    }

    private function is_reference($field)
    {
        return $this->resolve_reference($field) != null;
    }

    private function resolve_reference($field)
    {
        $id_column = $this->table()->id_column()->name();
        $result_set = new ResultSet($this->table());

        $result_set = $result_set->where($id_column, $this->$id_column);
        return $result_set->$field;
    }

    private function is_one_to_one_reference($field)
    {
        $fk = $field . '_id';
        return $this->table()->has_column($fk)
               && $this->table()->has_foreign_key($fk)
               && isset($this->values[$fk]);
    }

    private function resolve_one_to_one_reference($field)
    {
        $table_name = $this->table()->get_foreign_key_table_name($field . '_id');
        $fk_id = $this->values[$field . '_id'];
        return $this->table()->database()->table($table_name)->row($fk_id);
    }

    function save()
    {
        $this->table()->_save($this);
    }

}
