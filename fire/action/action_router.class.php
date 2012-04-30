<?php

class ActionRouter
{

    /* @var $class_loader ClassLoader */
    private $class_loader;

    /* @var $event_dispatcher EventDispatcher */
    private $event_dispatcher;

    /* @var $route_matcher RouteMatcher */
    private $route_matcher;

    function __construct(RouteMatcher $route_matcher, ClassLoader $class_loader, EventDispatcher $event_dispatcher, $routes = array())
    {
        $this->route_matcher = $route_matcher;
        $this->class_loader = $class_loader;
        $this->event_dispatcher = $event_dispatcher;
        foreach ($routes as $url => $action) {
            $this->add($url, $action);
        }
    }

    function add($url, $action)
    {
        $this->route_matcher->add($url, $action);
    }

    function route_request()
    {
        $url = $this->get_url();
        return $this->execute($url);
    }

    function execute($url)
    {
        $target = $this->route_matcher->route($url);

        if ($target == $url)
            throw new Exception("Invalid route $url");

        $parts = explode('/', $target);

        $action_class = $parts[0];
        $args = array_slice($parts, 1);

        /* @var $action Action */
        $action = $this->class_loader->init_subclass('Action', $action_class);

        $this->event_dispatcher->trigger('before_request', array('url' => $url));
        $result = call_user_func_array(array($action, 'execute'), $args);
        $this->event_dispatcher->trigger('after_request', array('url' => $url));

        return $result;
    }

    function get_url()
    {
        return isset($_SERVER['PATH_INFO'])
                ? substr($_SERVER['PATH_INFO'], 1)
                : '';
    }

}
