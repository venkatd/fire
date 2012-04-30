<?php

class RouteMapper_Tests extends PHPUnit_Framework_TestCase
{

    /**
     * @var RouteMatcher
     */
    private $matcher;

    function setUp()
    {
        require_once __DIR__ . '/../routemapper.class.php';
        $this->matcher = new RouteMapper();
    }

    function test_numeric_match()
    {

    }

}
