<?php

class ArrayCacheDriver extends CacheDriver
{

    private $array = array();

    function __construct($options)
    {
        parent::__construct($options);
    }

    function get($key)
    {
        if ($this->exists($key))
            return $this->array[$key];
    }

    function set($key, $data)
    {
        $this->array[$key] = $data;
    }

    function exists($key)
    {
        return isset($this->array[$key]);
    }

    function delete($key)
    {
        unset($this->array[$key]);
    }
    
}