<?php

class DatabaseTableGateway
{

    /* @var $database Database */
    private $database;

    private $rows = array();
    private $table_name;
    private $id_column;

    function __construct(Database $database, $table_name, $id_column)
    {
        $this->database = $database;
        $this->table_name = $table_name;
        $this->id_column = $id_column;
    }

    function get($id)
    {
        $this->reload_if_necessary($id);
        return $this->rows[$id];
    }

    function prefetch($query, $params = array())
    {
        $id_column = $this->id_column;

        $query = "SELECT * FROM $this->table_name AS table_gateway_prefetch WHERE $id_column IN ($query)";
        $stmt = $this->database->query_statement($query, $params);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
            $id = $row->$id_column;
            if (!isset($this->rows[$id]))
                $this->rows[$id] = (array)$row;
        }
    }

    function has($id)
    {
        return $this->get($id) != null;
    }

    function create($values)
    {
        $query = $this->create_row_query($values);
        $query->execute();

        $id = $this->database->last_insert_id();

        if (!$id) { // not an auto-increment column
            $id = $values[$this->id_column];
        }

        $this->reload($id);

        return $id;
    }

    function update($id, $values)
    {
        assert($id != null);

        $query = $this->update_row_query($id, $values);
        $query->execute();

        $this->reload($id);

        return $this->get($id);
    }

    function create_or_update($values)
    {
        $query = $this->create_or_update_row_query($values);
        $query->execute();

        $id = $this->database->last_insert_id();

        if (!$id) // in case there is no auto-increment
            $id = intval($values[$this->id_column]);

        $this->clear($id);

        return $id;
    }

    function destroy($id)
    {
        $query = $this->destroy_row_query($id);
        $query->execute();

        $this->reload($id);
        return $this->get($id);
    }

    function reload($id)
    {
        $this->rows[$id] = $this->fetch_values_from_id($id);
    }

    private function clear($id)
    {
        unset($this->rows[$id]);
    }

    private function reload_if_necessary($id)
    {
        if (!isset($this->rows[$id]))
            $this->reload($id);
    }

    private function fetch_values_from_id($id)
    {
        $query = $this->database->query_statement("SELECT * FROM $this->table_name WHERE $this->id_column = :id", array('id' => $id));
        $query->execute();
        $row_count = $query->rowCount();
        return $row_count > 0 ? (array)$query->fetchObject() : false;
    }

    private function create_row_query(array $values)
    {
        $columns = array_keys($values);
        $columns_sql = implode(', ', $columns);

        $values_sql = array();
        foreach ($columns as $column) {
            $values_sql[] = ":$column";
        }
        $values_sql = implode(', ', $values_sql);

        $sql = "INSERT INTO $this->table_name ($columns_sql) VALUES ($values_sql)";
        return $this->database->query_statement($sql, $values);
    }

    private function update_row_query($id, array $values)
    {
        $columns = array_keys($values);
        $set_statements = array();
        foreach ($columns as $column) {
            $set_statements[] = "$column = :$column";
        }

        $set_statements = implode(', ', $set_statements);

        $sql = "UPDATE $this->table_name SET $set_statements\n";
        $sql .= " WHERE $this->id_column = :id";

        $values['id'] = $id;

        return $this->database->query_statement($sql, $values);
    }

    private function destroy_row_query($row_id)
    {
        $sql = "DELETE FROM $this->table_name WHERE $this->id_column = :id";
        return $this->database->query_statement($sql, array('id' => $row_id));
    }

    private function create_or_update_row_query(array $values)
    {
        $id_column = $this->id_column;

        $columns = array_keys($values);
        $columns_sql = implode(', ', $columns);

        $values_sql = array();
        foreach ($columns as $column) {
            $values_sql[] = ":$column";
        }
        $values_sql = implode(', ', $values_sql);

        //build update statements
        $updates_sql = array();
        $updates_sql[] = "$id_column = LAST_INSERT_ID($id_column)";
        foreach ($columns as $column) {
            //we have a special update for the id column
            if ($column == $id_column) {
                continue;
            }
            else {
                $updates_sql[] = "$column = :update_{$column}";
                $values['update_' . $column] = $values[$column];
            }
        }

        $sql = "INSERT INTO $this->table_name ($columns_sql) VALUES ($values_sql)";
        $sql .= "\n  ON DUPLICATE KEY UPDATE " . implode(', ', $updates_sql);

        return $this->database->query_statement($sql, $values);
    }

}
