<?php

class RouteMatcher
{

    protected $routes = array();

    function add($src, $dest = null)
    {
        if ($src == '/')
            $src = '';

        if (is_array($src)) {
            foreach ($src as $key => $val) {
                $this->routes[$key] = $val;
            }
        }
        elseif ($dest) {
            $this->routes[$src] = $dest;
        }
    }

    function route($uri)
    {
        // Is there a literal match?
        if (isset($this->routes[$uri])) {
            return $this->routes[$uri];
        }

        // Loop through the route array looking for wild-cards
        foreach ($this->routes as $key => $val) {
            // Convert wild-cards to RegEx
            $key = str_replace(':any', '.+', $key);
            $key = str_replace(':num', '[0-9]+', $key);
            $key = str_replace(':nonum', '[^0-9]+', $key);
            $key = str_replace(':alpha', '[A-Za-z]+', $key);
            $key = str_replace(':alnum', '[A-Za-z0-9]+', $key);
            $key = str_replace(':hex', '[A-Fa-f0-9]+', $key);

            // Does the RegEx match?
            if (preg_match('#^' . $key . '$#', $uri)) {
                // Do we have a back-reference?
                if (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                    $val = preg_replace('#^' . $key . '$#', $val, $uri);
                }

                return $val;
            }
        }

        return $uri;
    }

}
