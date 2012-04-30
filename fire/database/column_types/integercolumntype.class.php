<?php

class IntegerColumnType extends ColumnType
{

    function to_sql()
    {
        $sql = array();

        $sql[] = 'INTEGER';
        if ( ! isset($this->options['unsigned']) || $this->options['unsigned'] == true) {
            $sql[] = 'UNSIGNED';
        }

        if (isset($this->options['default'])) {
            $default_value = $this->options['default'];
            $sql[] = "DEFAULT '$default_value'";
        }

        return implode(' ', $sql);
    }

}
