<?php

class CreateTableParser
{

    private $commas_regex = '/(\s|\n)*,(\s|\n)*/';
    private $table_name_regex = '/CREATE TABLE `(?P<name>\w+)` \([^)]+\)/';
    private $column_regex = '/^`(?P<name>\w+)` (?P<schema>.+)$/';
    private $foreign_key_regex = '/CONSTRAINT `(?P<name>[^`]+)` FOREIGN KEY \(`(?P<column>\w+)`\) REFERENCES `(?P<referenced_table>\w+)` \(`(?P<referenced_column>\w+)`\)/';
    private $index_regex = '/KEY `(?P<name>[^`]+)` \((?<columns>[^)]+)\)/';
    
    function __construct()
    {
    }

    function parse($create_table_sql)
    {
        $table = array();

        $table['name'] = $this->get_table_name($create_table_sql);
        $table['columns'] = $this->get_columns($create_table_sql);
        
        $table['foreign_keys'] = $this->get_foreign_keys($create_table_sql);
        $table['indexes'] = $this->get_indexes($create_table_sql);
        
        return $table;
    }

    private function get_table_name($sql)
    {
        preg_match($this->table_name_regex, $sql, $m);
        return $m['name'];
    }

    private function get_columns($sql)
    {
        $columns = array();

        $statements = $this->get_statements($sql);
        foreach ($statements as $cur_statement) {
            if ($this->is_column_statement($cur_statement)) {
                $column = $this->get_column_from_sql($cur_statement);
                $columns[ $column['name'] ] = $column;
            }
        }
        
        return $columns;
    }

    private function get_foreign_keys($sql)
    {
        $foreign_keys = array();
        
        $statements = $this->get_statements($sql);
        foreach ($statements as $cur_statement) {
            if ($this->is_foreign_key_statement($cur_statement)) {
                $foreign_key = $this->get_foreign_key_from_sql($cur_statement);
                $foreign_keys[ $foreign_key['name'] ] = $foreign_key;
            }
        }

        return $foreign_keys;
    }

    private function get_indexes($sql)
    {
        $indexes = array();

        $statements = $this->get_statements($sql);
        foreach ($statements as $cur_statement) {
            if ($this->is_index_statement($cur_statement)) {
                $index = $this->get_index_from_sql($cur_statement);
                $indexes[ $index['name'] ] = $index;
            }
        }
        
        return $indexes;
    }

    private function get_column_from_sql($column_sql)
    {
        preg_match($this->column_regex, $column_sql, $m);
        return array(
            'name' => $m['name'],
            'schema' => $m['schema'],
        );
    }

    private function is_column_statement($statement)
    {
        return preg_match($this->column_regex, $statement) > 0;
    }

    private function get_foreign_key_from_sql($foreign_key_sql)
    {
        preg_match($this->foreign_key_regex, $foreign_key_sql, $m);
        return array(
            'name' => $m['name'],
            'column' => $m['column'],
            'referenced_table' => $m['referenced_table'],
            'referenced_column' => $m['referenced_column'],
        );
    }
    
    private function is_foreign_key_statement($foreign_key_sql)
    {
        return preg_match($this->foreign_key_regex, $foreign_key_sql, $m) > 0;
    }

    private function get_index_from_sql($key_sql)
    {
        preg_match($this->index_regex, $key_sql, $m);
        $columns = preg_split($this->commas_regex, $m['columns']);
        return array(
            'name' => $m['name'],
            'columns' => $columns,
        );
    }

    private function is_index_statement($key_sql)
    {
        return preg_match($this->index_regex, $key_sql, $m) > 0;
    }

    private function get_statements($sql)
    {
        $open_paren_position = strpos($sql, '(');
        $close_paren_position = strrpos($sql, ')');
        $statements = substr($sql, $open_paren_position + 1, $close_paren_position - $open_paren_position - 1);
        $statements = preg_split($this->commas_regex, $statements);
        return $statements;
    }

}
