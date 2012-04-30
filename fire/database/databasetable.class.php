<?php

class DatabaseTable implements Iterator
{

    /**
     * @var \Database
     */
    private $database;

    private $name;

    private $schema;

    /* @var $rows DatabaseRow[] */
    private $rows = array();
    public $columns = array();

    /* @var $table_cache DatabaseTableGateway */
    private $table_gateway;

    function __construct(Database $database, $table_name)
    {
        $this->database = $database;
        $this->_load_schema_from_database($table_name);

        $this->table_gateway = new DatabaseTableGateway($this->database(), $this->name(), $this->id_column()->name());
    }

    function name()
    {
        return $this->name;
    }

    /**
     * @return Database
     */
    function database()
    {
        return $this->database;
    }

    /**
     * @param  $id
     * @return DatabaseRow|null
     */
    function row($id)
    {
        if (!$this->table_gateway->has($id))
            return null;

        if (!isset($this->rows[$id])) {
            $values = $this->table_gateway->get($id);
            if (!$values)
                $this->rows[$id] = false;
            else
                $this->init_database_row($id);
        }

        return $this->rows[$id];
    }

    function prefetch($query, $params = array())
    {
        $this->table_gateway->prefetch($query, $params);
    }

    /**
     * @param  $row_id
     * @return bool
     */
    function row_exists($row_id)
    {
        return $this->table_gateway->has($row_id);
    }

    /**
     * @param array $values
     * @return DatabaseRow
     */
    function create_row($values = array())
    {
        $values = $this->format_values_for_database($values);
        $id = $this->table_gateway->create($values);
        return $this->init_database_row($id);
    }

    /**
     * @param array $values
     * @return DatabaseRow|null
     */
    function create_or_update_row($values = array())
    {
        $values = $this->format_values_for_database($values);
        $id = $this->table_gateway->create_or_update($values);

        $this->row($id)->_set_values($this->table_gateway->get($id));
        return $this->row($id);
    }

    function destroy_row($id)
    {
        $this->table_gateway->destroy($id);
        unset($this->rows[$id]);
    }

    function count()
    {
        $query = $this->database()->query_statement('SELECT COUNT(*) AS count FROM ' . $this->name());
        $query->execute();
        $count = $query->fetch(PDO::FETCH_COLUMN);
        return intval($count);
    }

    function _save(DatabaseRow $row)
    {
        assert($row->table() == $this);

        $id_column = $this->id_column()->name();
        $values = $row->changes();

        $values = $this->table_gateway->update($row->$id_column, $values);
        $row->_set_values($values);
    }

    /**
     * @param $id
     *
     * @return DatabaseRow
     */
    private function init_database_row($id)
    {
        assert(!isset($this->rows[$id]));

        $this->rows[$id] = new DatabaseRow($this, $id);
        $values = $this->table_gateway->get($id);
        $this->rows[$id]->_set_values($values);
        return $this->rows[$id];
    }

    /**
     * Schema functions
     */

    /**
     * @param  $name
     * @return DatabaseColumn
     */
    function column($name)
    {
        if (!isset($this->schema['columns'][$name]))
            return NULL;

        if (!isset($this->columns[$name])) {
            $column_options = $this->schema['columns'][$name];
            $column_type = $column_options['type'];
            $column = app()->class_loader()->init_subclass('databasecolumn', $column_type, $this, $column_options);
            $this->columns[$name] = $column;
        }

        return $this->columns[$name];
    }

    /**
     * @return DatabaseColumn
     */
    function id_column()
    {
        if (count($this->schema['primary_key']) == 1) {
            $pk = $this->schema['primary_key'][0];
            return $this->column($pk);
        }
        else {
            throw new IdColumnMissingException("ID column is missing for the table " . $this->name() . '.');
        }
    }

    function has_column($column_name)
    {
        return $this->column($column_name) != null;
    }

    function create_column($name, array $options)
    {
        $column_sql = $this->get_column_sql($name, $options);
        $query = $this->database->query_statement("ALTER TABLE $this->name ADD $column_sql");
        $query->execute();

        $this->_refresh_schema();

    }

    function rename_column($current_column_name, $new_column_name)
    {
        /* @var $column DatabaseColumn */
        $column = $this->columns[$current_column_name];

        $table_name = $this->name();
        $create_table_schema = $this->fetch_create_table_schema();
        $column_schema = $create_table_schema['columns'][$current_column_name]['schema'];
        $query = $this->database->query_statement("ALTER TABLE $table_name CHANGE $current_column_name $new_column_name $column_schema");
        $query->execute();

        $this->_refresh_schema();

        // change name on object
        unset($this->columns[$current_column_name]);
        $column->_set_name($new_column_name);
        $this->columns[$new_column_name] = $column;
    }

    function destroy_column($column_name)
    {
        if ($this->has_foreign_key($column_name))
            $this->destroy_foreign_key($column_name);

        $query = $this->database->query_statement("ALTER TABLE $this->name DROP COLUMN $column_name");
        $query->execute();

        $this->_refresh_schema();
        unset($this->columns[$column_name]);
    }

    function create_index($column)
    {
        $column_list = func_get_args();
        $column_list_sql = implode(', ', $column_list);
        $table_name = $this->name();
        $index_name = $this->get_index_name($column_list);

        $query = $this->database->query_statement("CREATE INDEX `$index_name` ON $table_name ($column_list_sql) USING BTREE");
        $query->execute();
    }

    function destroy_index($column)
    {
        $column_list = func_get_args();
        $table_name = $this->name();
        $index_name = $this->get_index_name($column_list);

        $query = $this->database->query_statement("DROP INDEX `$index_name` ON $table_name");
        $query->execute();
    }

    function has_index($column)
    {
        $column_list = func_get_args();

        $query_sql = "SELECT * FROM information_schema.statistics
                        WHERE index_schema = :database_name
                        AND table_name = :table_name
                        AND index_name = :index_name";

        $params = array(
            'database_name' => $this->database()->name(),
            'table_name' => $this->name(),
            'index_name' => $this->get_index_name($column_list),
        );

        $query = $this->database->query_statement($query_sql, $params);
        $query->execute();

        return $query->rowCount() > 0;
    }

    function create_unique_index($column)
    {
        $column_list = func_get_args();
        $column_list_sql = implode(', ', $column_list);
        $table_name = $this->name();
        $index_name = $this->get_index_name($column_list);

        $query = $this->database->query_statement("CREATE UNIQUE INDEX `$index_name` ON $table_name ($column_list_sql) USING BTREE");
        $query->execute();
    }

    private function get_index_name(array $columns)
    {
        $columns = array_map('strtolower', $columns);
        sort($columns, SORT_STRING);

        $index_name = implode(':', $columns);
        return $index_name;
    }

    function create_foreign_key($column, $referenced_table, $referenced_column)
    {
        $table_name = $this->name();
        $foreign_key_name = $this->get_foreign_key_name($column);

        if (!$this->database()->has_table($referenced_table))
            throw new TableMissingException();

        $query_sql = "ALTER TABLE $table_name
                        ADD CONSTRAINT `$foreign_key_name` FOREIGN KEY ($column) REFERENCES $referenced_table ($referenced_column)";
        $query = $this->database->query_statement($query_sql);
        $query->execute();

        $this->_refresh_schema();
    }

    function has_foreign_key($column)
    {
        $key_name = $this->get_foreign_key_name($column);
        return isset($this->schema['foreign_keys'][$key_name]);
    }

    function destroy_foreign_key($column)
    {
        if ($this->has_foreign_key($column)) {
            $table_name = $this->name();
            $foreign_key_name = $this->get_foreign_key_name($column);
            $query = $this->database->query_statement("ALTER TABLE $table_name DROP FOREIGN KEY `$foreign_key_name`");
            $query->execute();
            $this->_refresh_schema();
        }
    }

    /**
     * @param  $column_name
     * @return DatabaseTable|null
     */
    function get_foreign_key_table($column_name)
    {
        $table_name = $this->get_foreign_key_table_name($column_name);
        return $table_name ? $this->database()->table($table_name) : null;
    }

    /**
     * @param  $column_name
     * @return DatabaseColumn|null
     */
    function get_foreign_key_column($column_name)
    {
        $fk_column_name = $this->get_foreign_key_column_name($column_name);
        return $column_name ? $this->get_foreign_key_table($column_name)->column($fk_column_name) : null;
    }

    function get_foreign_key_table_name($column_name)
    {
        if ($this->has_foreign_key($column_name)) {
            $key_name = $this->get_foreign_key_name($column_name);
            return $this->schema['foreign_keys'][$key_name]['referenced_table'];
        }
        return null;
    }

    function get_foreign_key_column_name($column_name)
    {
        if ($this->has_foreign_key($column_name)) {
            $key_name = $this->get_foreign_key_name($column_name);
            return $this->schema['foreign_keys'][$key_name]['referenced_column'];
        }
        return null;
    }

    private function get_foreign_key_name($column)
    {
        $table_name = $this->name();
        return "$table_name.$column";
    }

    function _refresh_schema()
    {
        $this->_load_schema_from_database($this->name);
    }

    function _load_schema_from_database($table_name)
    {
        $this->name = $table_name;

        $schema = array(
            'name' => $table_name,
            'columns' => array(),
            'primary_key' => array(),
        );

        // we must fetch schema from two sources because the information schema table is incomplete
        $information_schema = $this->fetch_mysql_information_schema();
        $create_table_schema = $this->fetch_create_table_schema();

        $schema['foreign_keys'] = $create_table_schema['foreign_keys'];
        $schema['indexes'] = $create_table_schema['indexes'];

        foreach ($information_schema as $column_info) {
            $column = array(
                'name' => $column_info->Field,
                'type' => $this->get_data_type($column_info),
                'null' => ($column_info->Null == 'YES'),
                'description' => $column_info->Comment,
            );

            $column['default'] = $column_info->Default;

            $column['primary_key'] = $column_info->Key == 'PRI';
            $column['auto_increment'] = $column_info->Extra == 'auto_increment';

            //size of field
            $size = $this->get_mysql_data_size($column_info);
            if ($size !== false) {
                $column['size'] = $size;
            }

            $schema['columns'][$column['name']] = $column;
        }

        $query = $this->database->query_statement("SHOW INDEX FROM {$this->name}");
        $query->execute();
        $index_infos = $query->fetchAll(PDO::FETCH_OBJ);

        foreach ($index_infos as $column_info) {
            if ($column_info->Key_name == 'PRIMARY') {
                $schema['primary_key'][] = $column_info->Column_name;
            }
            elseif ($column_info->Non_unique == '0') { // means unique
                $schema['unique_keys'][$column_info->Key_name][] = $column_info->Column_name;
            }
            elseif ($column_info->Non_unique == '1') { // not unique.. just an index
                $schema['indexes'][$column_info->Key_name][] = $column_info->Column_name;
            }
        }

        $this->schema = $schema;
    }

    /**
     * @return ResultSet
     */
    function all()
    {
        return new ResultSet($this);
    }

    /**
     * @param  $field_name string
     * @param  $value mixed
     * @return ResultSet
     */
    function where($field_name, $value)
    {
        return $this->all()->where($field_name, $value);
    }

    /**
     * @param $field_name string
     * @param $order string
     * @return ResultSet
     */
    function order_by($field_name, $order = 'asc')
    {
        return $this->all()->order_by($field_name, $order);
    }

    /**
     * @param  $n
     * @return ResultSet
     */
    function limit($n)
    {
        return $this->all()->limit($n);
    }

    /* Iterator Methods */
    /**
     * @var TableQueryIterator
     */
    private $iterator;

    function current()
    {
        return $this->iterator->current();
    }

    function key()
    {
        return $this->iterator->key();
    }

    function next()
    {
        $this->iterator->next();
    }

    function rewind()
    {
        $id_column_name = $this->id_column()->name();
        $table_name = $this->name();
        $this->iterator = new TableQueryIterator($this, "SELECT $id_column_name AS id FROM $table_name");
        $this->iterator->rewind();
    }

    function valid()
    {
        return $this->iterator->valid();
    }

    private function fetch_create_table_schema()
    {
        $query = $this->database->query_statement("SHOW CREATE TABLE {$this->name}");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $create_table_statement = $result['Create Table'];
        $parser = new CreateTableParser();
        return $parser->parse($create_table_statement);
    }

    private function fetch_mysql_information_schema()
    {
        $query = $this->database->query_statement("SHOW FULL COLUMNS FROM {$this->name}");
        $query->execute();
        $column_schemas = $query->fetchAll(PDO::FETCH_OBJ);
        return $column_schemas;
    }

    private function get_data_type($field_info)
    {
        $mysql_type = $this->get_mysql_data_type($field_info);
        $mysql_size = $this->get_mysql_data_size($field_info);

        if ($mysql_type == 'tinyint' && $mysql_size == 1)
            return 'boolean';
        elseif ($mysql_type == 'varchar')
            return 'string';
        elseif ($mysql_type == 'int' || $mysql_type == 'bigint')
            return 'integer';
        elseif ($mysql_type == 'text')
            return 'text';
        elseif ($mysql_type == 'datetime')
            return 'time';
        elseif ($mysql_type == 'date')
            return 'date';
        else
            throw new Exception("No such MySql type $mysql_type");
    }

    private function get_mysql_data_type($field_info)
    {
        $matches = array();
        $num_matches = preg_match('/^(?<type>\w+)/', $field_info->Type, $matches);
        return $num_matches > 0 ? $matches['type'] : false;
    }

    private function get_mysql_data_size($field_info)
    {
        $matches = array();
        $num_matches = preg_match('/^\w+\((?<size>\d+)\)/', $field_info->Type, $matches);
        return $num_matches > 0 ? (int)$matches['size'] : false;
    }

    private function format_values_for_database($values)
    {
        foreach ($values as $column_name => &$column_value) {
            $column = $this->column($column_name);
            if (!$column)
                throw new Exception("Column $column_name is missing.");

            $column_value = $column->to_database_value($column_value);
        }
        return $values;
    }

    private function get_column_sql($column_name, $column_config)
    {
        $column_type = app()->class_loader()->init_subclass('columntype', $column_config['type'], $column_config);
        return $column_name . ' ' . $column_type->to_sql();
    }

}
