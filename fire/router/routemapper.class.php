<?php

class RouteMapper
{

    private $routes = array();

    private $patterns = array(
        'any' => '.+',
        'num' => '\d+',
        'nonum' => '\D+',
        'alpha' => '[A-Za-z]+',
        'word' => '\w+',
        'hex' => '[A-Fa-f0-9]+',
    );

    function add($pattern)
    {
        $this->routes[$pattern] = $pattern;

        $r = $this->regexify($pattern);
        krumo::dump($r);
    }

    function match($path)
    {
        foreach ($this->routes as $route) {
            $r_route = $this->regexify($route);
            $n = preg_match("/^$r_route$/", $path, $matches);
            if ($n == 1) {

            }
        }
    }

    function regexify($route)
    {
        foreach ($this->patterns as $type => $regex) {
            $route = str_replace(":$type", "(?P<$type>$regex)", $route);
        }
        return $route;
    }


}

