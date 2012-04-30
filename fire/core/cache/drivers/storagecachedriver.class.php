<?php

class StorageCacheDriver extends CacheDriver
{
    
    function __construct($options)
    {
        parent::__construct($options);
        $this->storage = $this->options['storage'];
    }

    /**
     * @return FileRepository
     */
    private function storage()
    {
        return $this->options['storage'];
    }

    function get($key)
    {
        if ($this->exists($key))
        {
            return $this->storage()->load_data($key);
        }
    }

    function set($key, $data)
    {
        $this->storage()->create_from_data($key, $data);
    }

    function exists($key)
    {
        return $this->storage()->exists($key);
    }

    function delete($key)
    {
        $this->storage()->delete($key);
    }

}
