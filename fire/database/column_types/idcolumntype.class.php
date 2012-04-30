<?php

class IdColumnType extends ColumnType
{

    protected $defaults = array(
        'auto_increment' => true,
    );

    function to_sql()
    {
        $type = $this->get_type();
        $sql = array($type, 'UNSIGNED');

        if ($this->options['auto_increment'])
            $sql[] = 'AUTO_INCREMENT';

        $sql[] = 'PRIMARY KEY';

        $sql[] = 'NOT NULL';

        return implode(' ', $sql);
    }

    private function get_type()
    {
        if (isset($this->options['length']) && $this->options['length'] == 'big')
            return 'BIGINT';
        else
            return 'INTEGER';
    }

}
