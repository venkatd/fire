<?php

abstract class DatabaseLink
{

    /* @var $prev DatabaseLink|null */
    public $prev;

    /* @var $next DatabaseLink|null */
    public $next;

    function __toString()
    {
        throw new Exception('Not implemented.');
    }
}
