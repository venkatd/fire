<?php

require_once 'core.functions.php';

class FireApp
{

    public $window_settings = array();

    /**
     * @var ClassLoader
     */
    protected $class_loader;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var EventDispatcher
     */
    protected $event_dispatcher;
    
    function __construct(ClassLoader $class_loader, Database $database, EventDispatcher $event_dispatcher)
    {
        $this->class_loader = $class_loader;
        $this->database = $database;
        $this->event_dispatcher = $event_dispatcher;
    }

    function database()
    {
        return $this->database;
    }

    function load_window_settings()
    {
        $js = '<script type="text/javascript">'
              . 'window.settings = ' . json_encode($this->window_settings) . ';'
              . '</script>';
        return $js;
    }
        
    function trigger($event_name, $event_data = array())
    {
        $this->event_dispatcher->trigger($event_name, $event_data);
    }

    /**
     * @return ClassLoader
     */
    function class_loader()
    {
        return $this->class_loader;
    }

    /**
     * @return Index
     */
    function index()
    {
        return $this->class_loader->get_index();
    }

    function enable_autoload()
    {
        $this->class_loader->enable_autoload();
    }

}
