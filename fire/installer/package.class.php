<?php

abstract class Package
{

    public $version = '0.1';

    /**
     * @var Database
     */
    protected $database;

    function __construct(Database $database)
    {
        $this->database = $database;
    }

    function install()
    {
    }

    function uninstall()
    {
    }

}
