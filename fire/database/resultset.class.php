<?php

class ResultSet implements Iterator
{

    /* @var DatabaseField */
    public $select_field;

    /* @var DatabaseWhereFilter[] */
    private $filters = array();

    /* @var DatabaseLimit|null */
    private $limit = null;

    /* @var DatabaseOrderBy|null */
    private $order_by = null;

    /* @var $link_resolver DatabaseLinkResolver */
    private $link_resolver;

    function __construct(DatabaseTable $base_table)
    {
        $this->set_base_table($base_table);
        $this->link_resolver = new DatabaseLinkResolver();
    }

    function set_base_table(DatabaseTable $base_table)
    {
        $id_column_name = $base_table->id_column()->name();
        $this->select_field = new DatabaseField($base_table, $id_column_name);
    }

    /**
     * @param  $field_name
     * @param  $field_value
     * @param $operator
     * @return ResultSet
     */
    function where($field_name, $field_value, $operator = '=')
    {
        $set = clone $this;
        $set->add_where($field_name, $field_value, $operator);
        return $set;
    }

    function add_where($field_name, $field_value, $operator = '=')
    {
        benchmark::start(__METHOD__);

        benchmark::start(__METHOD__ . '::column table');
        $table = $this->select_field->column()->table();
        benchmark::end(__METHOD__ . '::column table');

        benchmark::start(__METHOD__ . '::new DatabaseField');
        $field = new DatabaseField($table, $field_name);
        benchmark::end(__METHOD__ . '::new DatabaseField');

        benchmark::start(__METHOD__ . '::new DatabaseWhereFilter');
        $this->filters[] = new DatabaseWhereFilter($field, $field_value, $operator);
        benchmark::end(__METHOD__ . '::new DatabaseWhereFilter');

        benchmark::end(__METHOD__);
    }

    /**
     * @param  $field_name
     * @param string $sort
     * @return ResultSet
     */
    function order_by($field_name, $sort = 'asc')
    {
        $set = clone $this;
        $set->set_order_by($field_name, $sort);
        return $set;
    }

    function set_order_by($field_name, $sort = 'asc')
    {
        benchmark::start(__METHOD__);

        $table = $this->select_field->column()->table();
        $fields = is_string($field_name) ? array($field_name => $sort) : $field_name;

        $orders = array();
        foreach ($fields as $field_name => $sort) {
            $field = new DatabaseField($table, $field_name);
            $orders[] = array('field' => $field, 'sort' => $sort);
        }

        $this->order_by = new DatabaseOrderBy($orders);

        benchmark::end(__METHOD__);
    }

    /**
     * @param  $count
     * @return ResultSet
     */
    function limit($count, $offset = 0)
    {
        $set = clone $this;
        $set->set_limit($count, $offset);
        return $set;
    }

    function set_limit($count, $offset = 0)
    {
        $this->limit = new DatabaseLimit($count, $offset);
    }

    /**
     * @return DatabaseField[]
     */
    function required_fields()
    {
        $fields = array();

        $fields[] = $this->select_field;

        foreach ($this->filters as $filter)
            $fields = array_merge($fields, $filter->required_fields());

        if ($this->order_by)
            $fields = array_merge($fields, $this->order_by->required_fields());

        return $fields;
    }

    /**
     * @return DatabaseTableLink[]
     */
    function get_required_links()
    {
        $links = array();

        foreach ($this->required_fields() as $field) {
            $current_path = array();
            /* @var $link DatabaseTableLink */
            foreach ($field->link_path->links as $link) {
                if ($link instanceof DatabaseTableLink) {
                    $current_path[] = $link->left_table->name()
                            . ':' . $link->left_column->name()
                            . ':' . $link->right_table->name()
                            . ':' . $link->right_column->name();
                    $current_alias = implode('->', $current_path);
                    $links[$current_alias] = $link;
                }
            }
        }

        return $links;
    }

    private function get_join_sql()
    {
        benchmark::start(__METHOD__);
        $sql = array();

        $currently_joined_tables = array();

        foreach ($this->required_fields() as $field) {
            $right_table_alias = $this->select_field->table_alias();
            /* @var $link DatabaseTableLink */
            foreach ($field->link_path->links as $link) {
                if ($link instanceof DatabaseTableLink) {

                    $left_table_alias = $right_table_alias;
                    $right_table_alias = $field->link_path->get_link_alias($link);

                    // table has already been joined, so we don't need to join it again
                    if (isset($currently_joined_tables[$right_table_alias])) {
                        continue;
                    }

                    $sql[] = "\n  INNER JOIN " . $link->right_table->name() . " AS $right_table_alias"
                            . " ON " . $left_table_alias . "." . $link->left_column->name()
                            . " = " . $right_table_alias . "." . $link->right_column->name();

                    $currently_joined_tables[$right_table_alias] = true;
                }
            }
        }

        benchmark::end(__METHOD__);

        return implode('', $sql);
    }

    function to_sql()
    {
        benchmark::start(__METHOD__);
        $sql = array();

        $select_field_table_alias = $this->select_field->table_alias();
        $sql[] = 'SELECT ' . $this->select_field->to_sql() . ' AS id FROM '
                . $this->select_field->column()->table()->name() . ' AS ' . $select_field_table_alias;

        $sql[] = $this->get_join_sql();

        if (count($this->filters) > 0)
            $sql[] = "\n  " . $this->get_where_sql();

        if ($this->order_by)
            $sql[] = "\n  " . $this->order_by->to_sql();

        if ($this->limit)
            $sql[] = "\n  " . $this->limit->to_sql();

        benchmark::end(__METHOD__);

        return implode('', $sql);
    }

    function to_array()
    {
        $array = array();
        $set = clone $this;
        foreach ($set as $result) {
            $array[] = $result;
        }
        return $array;
    }

    function parameters()
    {
        $params = array();
        foreach ($this->filters as $filter) {
            $params = array_merge($params, $filter->parameters());
        }
        return $params;
    }

    function __clone()
    {
        $this->select_field = clone $this->select_field;

        if ($this->order_by)
            $this->order_by = clone $this->order_by;

        foreach ($this->filters as &$filter) {
            $filter = clone $filter;
        }
    }

    function __get($field_name)
    {
        $field = new DatabaseField($this->table(), $field_name);
        if ($field->is_table_field()) {
            $reversed_link_path = $field->link_path->reverse();

            $set = clone $this;
            // make select field the newly referenced table
            $set->select_field = new DatabaseField($field->table(), $field->table()->id_column()->name());
            // extend link path so it is still connected to the base table
            foreach ($set->filters as &$filter) {
                $filter->field->link_path = $reversed_link_path->add_link_path($filter->field->link_path);
            }
            return $set;
        }
        else {
            return null;
        }
    }

    /**
     * @return DatabaseRow|null
     */
    function first()
    {
        foreach ($this->limit(1) as $item) {
            return $item;
        }
        return null;
    }

    function count()
    {
        $query = $this->database()->query_statement($this->to_sql(), $this->parameters());
        $query->execute();
        return $query->rowCount();
    }

    /* Iterator Methods */
    /**
     * @var TableQueryIterator
     */
    private $iterator;

    function current()
    {
        benchmark::start(__METHOD__);
        $current = $this->iterator->current();
        benchmark::end(__METHOD__);

        return $current;
    }

    function key()
    {
        return $this->iterator->key();
    }

    function next()
    {
        benchmark::start(__METHOD__);
        $next = $this->iterator->next();
        benchmark::end(__METHOD__);

        return $next;
    }

    function rewind()
    {
        benchmark::start(__METHOD__);
        $table = $this->table();
        $sql = $this->to_sql();
        $params = $this->parameters();
        $this->iterator = new TableQueryIterator($table, $sql, $params);

        $this->iterator->rewind();
        benchmark::end(__METHOD__);
    }


    function valid()
    {
        return $this->iterator->valid();
    }

    function destroy()
    {
        foreach ($this as $row_id => $row) {
            $this->table()->destroy_row($row_id);
        }
    }

    function collect($field)
    {
        $values = array();
        foreach ($this as $row) {
            $values[] = $row->$field;
        }
        return $values;
    }

    function prefetch()
    {
        $this->table()->prefetch($this->to_sql(), $this->parameters());
    }

    /**
     * @return DatabaseTable
     */
    function table()
    {
        return $this->select_field->column()->table();
    }

    /**
     * @return Database
     */
    function database()
    {
        return $this->table()->database();
    }

    /**
     * @return DatabaseTableJoin[]
     */
    private function joins()
    {
        $joins = array();
        foreach ($this->filters as $filter) {
            foreach ($filter->joins() as $join) {
                $joins[$join->join_table->name()] = $join;
            }
        }

        if ($this->order_by) {
            foreach ($this->order_by->joins() as $join) {
                $joins[$join->join_table->name()] = $join;
            }
        }

        return $joins;
    }

    private function get_where_sql()
    {
        $sql = array();

        foreach ($this->filters as $filter) {
            $sql[] = $filter->to_sql();
        }

        return "WHERE " . implode(' AND ', $sql);
    }

}
