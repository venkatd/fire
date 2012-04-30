<?php

abstract class CacheDriver extends Driver
{
    abstract function get($key);
    abstract function set($key, $data);
    abstract function exists($key);
    abstract function delete($key);
}