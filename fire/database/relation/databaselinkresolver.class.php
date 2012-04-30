<?php

class DatabaseLinkResolver
{

    /**
     * @param DatabaseTable $left_table
     * @param $field_name
     * @return DatabaseLinkPath
     */
    function resolve_link_path(DatabaseTable $left_table, $field_name)
    {
        $path = new DatabaseLinkPath();

        $field_parts = explode('.', $field_name);
        $current_left_table = $left_table;

        foreach ($field_parts as $current_field_part) {
            $links = $this->resolve_links($current_left_table, $current_field_part);

            if (!$links)
                return false;

            foreach ($links as $link) {
                $path->add_link($link);

                if ($link instanceof DatabaseTableLink)
                    $current_left_table = $link->right_table;
            }
        }

        return $path;
    }

    /**
     * @param DatabaseTable $left_table
     * @param  $field_name
     * @return DatabaseLink[]|null
     */
    function resolve_links(DatabaseTable $left_table, $field_name)
    {
        if ($this->is_column_link($left_table, $field_name)) {
            return $this->get_column_link($left_table, $field_name);
        }

        elseif ($this->is_simple_foreign_key_link($left_table, $field_name)) {
            return $this->get_simple_foreign_key_link($left_table, $field_name);
        }

        elseif ($this->is_direct_table_link($left_table, $field_name)) {
            return $this->get_direct_table_link($left_table, $field_name);
        }

        elseif ($this->is_join_table_link($left_table, $field_name)) {
            return $this->get_join_table_link($left_table, $field_name);
        }

        return null;
    }

    function is_column_link(DatabaseTable $left_table, $field_name)
    {
        return $left_table->has_column($field_name);
    }

    function get_column_link(DatabaseTable $left_table, $field_name)
    {
        return array(
            new DatabaseColumnLink($left_table, $left_table->column($field_name)),
        );
    }

    function is_simple_foreign_key_link(DatabaseTable $left_table, $field_name)
    {
        return $left_table->has_column($field_name . '_id');
    }

    function get_simple_foreign_key_link(DatabaseTable $left_table, $field_name)
    {
        $left_column = $left_table->column($field_name . '_id');
        $right_column = $left_table->get_foreign_key_column($field_name . '_id');
        return array(new DatabaseTableLink($left_column, $right_column));
    }

    function is_direct_table_link(DatabaseTable $left_table, $field_name)
    {
        $db = $left_table->database();

        if (!$db->has_table($field_name))
            return false;

        $right_table = $db->table($field_name);

        $right_column_name = Inflect::singularize($left_table->name()) . '_id';
        $right_column = $right_table->column($right_column_name);

        if (!$right_column)
            return false;

        $left_column = $right_table->get_foreign_key_column($right_column_name);

        if (!$left_column)
            return false;

        return true;
    }

    function get_direct_table_link(DatabaseTable $left_table, $field_name)
    {
        $right_table = $left_table->database()->table($field_name);
        $right_column_name = Inflect::singularize($left_table->name()) . '_id';
        $right_column = $right_table->column($right_column_name);
        $left_column = $right_table->get_foreign_key_column($right_column_name);
        return array(new DatabaseTableLink($left_column, $right_column));
    }

    function is_join_table_link(DatabaseTable $left_table, $field_name)
    {
        $db = $left_table->database();
        $left_table_name = $left_table->name();
        $join_table_name = Inflect::singularize($left_table_name) . '_' . $field_name;
        if (!$db->has_table($join_table_name))
            return false;

        return true;
    }

    function get_join_table_link(DatabaseTable $left_table, $field_name)
    {
        // left_table (left_column) -> (left_join_column) join_table
        // join_table (right_join_column) -> (right_column) right_table

        $links = array();

        $db = $left_table->database();

        $join_table_name = Inflect::singularize($left_table->name()) . '_' . $field_name;
        $join_table = $db->table($join_table_name);

        $left_join_column_name = Inflect::singularize($left_table->name()) . '_id';
        $left_join_column = $join_table->column($left_join_column_name);

        $right_join_column_name = Inflect::singularize($field_name) . '_id';
        $right_join_column = $join_table->column($right_join_column_name);

        $left_column = $join_table->get_foreign_key_column($left_join_column_name);
        $right_column = $join_table->get_foreign_key_column($right_join_column_name);

        $links[] = new DatabaseTableLink($left_column, $left_join_column);
        $links[] = new DatabaseTableLink($right_join_column, $right_column);

        return $links;
    }

}
