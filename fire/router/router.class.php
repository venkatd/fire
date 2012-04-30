<?php

class Router
{

    private $app;
    private $routes;

    private $class;
    private $method;
    private $routed_segments;

    function __construct(FireApp $app, array $routes = array())
    {
        $this->app = $app;
        $this->routes = $routes;
    }

    function route_request()
    {
        $this->determine_route();
        $controller_class = $this->get_class();
        $controller_method = $this->get_method();

        if ($this->is_valid_request()) {
            $controller = new $controller_class;
        }

        if ($this->is_valid_request() && is_callable(array($controller, $controller_method))) {
            $routed_segments = $this->get_routed_segments();
            $this->app->trigger('before_controller_request', array(
                                                             'url' => $this->get_url(),
                                                        ));

            call_user_func_array(array(&$controller, $controller_method), array_slice($routed_segments, 2));

            $this->app->trigger('after_controller_request', array(
                                                            'url' => $this->get_url(),
                                                       ));

        }
        else {
            print "<h1>404 page not found</h1>";
        }
    }

    function get_url()
    {
        return isset($_SERVER['PATH_INFO'])
                ? substr($_SERVER['PATH_INFO'], 1)
                : '';
    }

    function get_url_segments()
    {
        return explode('/', $this->get_url());
    }

    function determine_route()
    {
        $url = $this->get_url();

        //route the uri if a route exists
        $matcher = new RouteMatcher();
        if (isset($this->routes['/'])) {
            $this->routes[''] = $this->routes['/'];
            unset($this->routes['/']);
        }
        foreach ($this->routes as $k => $v) {
            $matcher->add($k, $v);
        }
        $routed_url = $matcher->route($url);

        $this->routed_segments = explode('/', $routed_url);

        $class = $this->routed_segments[0] . '_controller';
        $method = isset($this->routed_segments[1]) ? $this->routed_segments[1] : 'index';

        $this->class = $class;
        $this->method = $method;

        $this->validate_request();
    }

    function get_request()
    {
        return implode('/', $this->uri->segments);
    }

    private $valid_request = false;

    private function validate_request()
    {
        $this->valid_request = true;
        if (!class_exists($this->class))
            $this->valid_request = false;
        elseif (!is_subclass_of($this->class, 'Controller'))
            $this->valid_request = false;
        elseif (!method_exists($this->class, $this->method))
            $this->valid_request = false;
        elseif (substr($this->method, 0, 1) == '_')
            $this->valid_request = false;
    }

    function is_valid_request()
    {
        return $this->valid_request;
    }

    function get_method()
    {
        return $this->method;
    }

    function get_class()
    {
        return $this->class;
    }

    function get_routed_segments()
    {
        return $this->routed_segments;
    }

}
