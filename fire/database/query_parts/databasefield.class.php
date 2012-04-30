<?php

class DatabaseField extends QueryPart
{

    /* @var $links DatabaseLinkPath */
    public $link_path;

    private $link_resolver;

    function __construct(DatabaseTable $table, $field_name)
    {
        $this->link_resolver = new DatabaseLinkResolver();

        $this->link_path = $this->link_resolver->resolve_link_path($table, $field_name);
    }

    /**
     * @return bool
     */
    function is_valid()
    {
        return $this->link_path != null;
    }

    /**
     * @return bool
     */
    function is_column_field()
    {
        if (!$this->is_valid())
            return null;

        return $this->column() != null;
    }

    /**
     * @return bool
     */
    function is_table_field()
    {
        if (!$this->is_valid())
            return null;

        return !$this->is_column_field()
             && $this->table() != null;
    }

    function __clone()
    {
        if ($this->link_path)
            $this->link_path = clone $this->link_path;
    }

    function to_sql()
    {
        if ($this->column()) {
            return $this->table_alias() . '.' . $this->column()->name();
        }

        return null;
    }

    /**
     * @return string
     */
    function table_alias()
    {
        return $this->link_path->get_link_alias($this->last_link());
    }

    /**
     * @return DatabaseTable|null
     */
    function table()
    {
        $links = array_reverse($this->link_path->links);
        foreach ($links as $link) {
            /* @var $link DatabaseTableLink */
            if ($link instanceof DatabaseTableLink)
                return $link->right_table;
        }

        return null;
    }

    /**
     * @return DatabaseColumn|null
     */
    function column()
    {
        if (!$this->is_valid())
            return null;

        $count = count($this->link_path->links);
        $last_link = $this->link_path->links[$count - 1];

        if ($last_link instanceof DatabaseColumnLink) {
            /* @var $last_link DatabaseColumnLink */
            return $last_link->right_column;
        }

        return null;
    }

    /**
     * @return DatabaseLink|null
     */
    function last_link()
    {
        if (!$this->is_valid())
            return null;

        $count = count($this->link_path->links);
        return $this->link_path->links[$count - 1];
    }

}
