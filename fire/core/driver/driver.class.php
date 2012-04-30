<?php

abstract class Driver
{

    private $name;
    protected $options;

    function __construct($options = array())
    {
        $this->options = $options;
    }

    function name()
    {
        if (!$this->name) {
            $parent_class = strtolower(get_parent_class($this));
            $this_class = strtolower(get_class($this));
            return preg_replace("/$parent_class$/", '', $this_class);
        }
    }

}
