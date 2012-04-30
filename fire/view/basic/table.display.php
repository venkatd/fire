<?php

class Table_Display extends Display
{

    protected $defaults = array(
        'columns' => 'auto',
        'class' => '',
        'attributes' => array(),
    );

    function process()
    {
        if ($this->columns == 'auto') {
            $this->columns = $this->extract_columns();
        }

        $attributes = $this->attributes;
        $attributes['class'] = $this->class;
        $this->attributes = $attributes;
    }

    private function extract_columns()
    {
        $columns = array();
        foreach ($this->rows as $row) {
            if (is_array($row)) {
                $columns = array_merge($columns, array_keys($row));
            }
            elseif (is_object($row)) {
                $columns = array_merge($columns, array_keys(get_object_vars($row)));
            }
        }
        return array_unique($columns);
    }

}
