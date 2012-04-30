<?php

class Flow
{

    public $name;

    /**
     * @static
     * @return Flow|null
     */
    public static function get()
    {
        return isset($_SESSION['__flow']) ? $_SESSION['__flow'] : null;
    }

    public static function name()
    {
        return static::get() ? static::get()->name : null;
    }

    public static function set(Flow $flow)
    {
        $_SESSION['__flow'] = $flow;
    }

    public static function end()
    {
        unset($_SESSION['__flow']);
    }

}
