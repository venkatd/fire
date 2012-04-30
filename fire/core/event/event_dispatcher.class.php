<?php

class EventDispatcher
{

    /* @var $class_loader ClassLoader */
    private $class_loader;

    private $plugins = array();

    function __construct(ClassLoader $class_loader)
    {
        $this->class_loader = $class_loader;
    }

    function trigger($event_name, $event_data = array())
    {
        $this->load_plugins_if_not_loaded();

        $e = $this->cast_event($event_name, $event_data);
        foreach ($this->plugins as $plugin_name => $plugin_instance) {
            $handler = "on_$event_name";

            if (method_exists($plugin_instance, $handler)) {
                $plugin_instance->$handler($e);
            }
        }

        return $e;
    }

    private function cast_event($event_name, $event_data = array())
    {
        $e = is_object($event_data) ? $event_data : (object)$event_data;
        $e->type = $event_name;

        $event_object = $this->class_loader->init_subclass('FireEvent', $e->type);
        if (!$event_object)
            $event_object = $this->class_loader->init('FireEvent');

        foreach ($e as $prop => $val)
            $event_object->$prop = $val;

        return $event_object;
    }

    private $plugins_loaded = false;

    private function load_plugins_if_not_loaded()
    {
        if ($this->plugins_loaded)
            return;

        $plugin_class_names = $this->class_loader->get_subclass_names('Plugin');
        foreach ($plugin_class_names as $class_name) {
            $plugin_name = strtolower($class_name);
            $this->plugins[$plugin_name] = $this->class_loader->init($class_name);
        }

        $this->plugins_loaded = true;
    }

}
