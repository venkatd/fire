<?php

class Cache extends Component
{

    private $cache = array();

    function __construct($options = array())
    {
        parent::__construct($options);
    }

    function get($key)
    {
        if ( ! isset($this->cache[$key]) ) {
            $this->cache[$key] = $this->driver()->get($key);
        }
        return $this->cache[$key];
    }

    function set($key, $data)
    {
        $this->cache[$key] = $data;
        $this->driver()->set($key, $data);
    }

    function exists($key)
    {
        if (isset($this->cache[$key]))
            return true;
        
        return $this->driver()->exists($key);
    }

    function missing($key)
    {
        return !$this->exists($key);
    }

    function delete($key)
    {
        unset($this->cache[$key]);
        $this->driver()->delete($key);
    }

}
