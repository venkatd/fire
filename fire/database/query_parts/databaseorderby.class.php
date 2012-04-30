<?php

class DatabaseOrderBy extends QueryPart
{

    private $orders;

    function __construct(array $orders)
    {
        $this->orders = $orders;
    }

    function to_sql()
    {
        $parts = array();
        foreach ($this->orders as $order) {
            $parts[] = $order['field']->to_sql() . ' ' . strtoupper($order['sort']);
        }
        return 'ORDER BY ' . implode(', ', $parts);
    }

    /**
     * @return DatabaseField[]
     */
    function required_fields()
    {
        $fields = array();
        foreach ($this->orders as $order) {
            $fields[] = $order['field'];
        }
        return $fields;
    }

    /**
     * @return DatabaseTableJoin[]
     */
    function joins()
    {
        $joins = array();
        foreach ($this->orders as $order)
            $joins = array_merge($joins, $order['field']->joins());
    }
    
}
