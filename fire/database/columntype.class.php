<?php

abstract class ColumnType
{

    protected $options = array();
    protected $defaults = array();

    function __construct($options)
    {
        $this->options = $options;
        $this->set_defaults($this->options, $this->defaults);
    }

    abstract function to_sql();

    private function set_defaults(&$array, $defaults = array())
    {
        $array = array_merge($defaults, $array);
    }

}
