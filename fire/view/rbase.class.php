<?php

class rbase
{

    public static function __callStatic($view_name, $args)
    {
        $vars = isset($args[0]) ? $args[0] : array();
        return Display::r($view_name, $vars);
    }

    public static function deal_popup($vars = array())
    {
        return Display::r('deal_popup', $vars);
    }

}
