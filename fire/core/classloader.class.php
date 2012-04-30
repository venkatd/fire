<?php

class ClassLoader
{

    /**
     * @var Index
     */
    private $index;

    private $class_instances = array();

    function __construct(Index $index)
    {
        $this->index = $index;
    }

    function init($class_name)
    {
        $this->load($class_name);

        $reflection = new ReflectionClass($class_name);
        $args = array_slice(func_get_args(), 1);

        //php fug where empty args still throws an exception
        $instance = empty($args) ? new $class_name
                                 : $reflection->newInstanceArgs($args);

        return $instance;
    }

    function init_subclass($superclass, $subclass)
    {
        $subclass_name = $this->get_subclass_name($superclass, $subclass);
        if ($subclass_name) {
            $args = array_slice(func_get_args(), 2);
            array_unshift($args, $subclass_name);
            $instance = call_user_func_array(array($this, 'init'), $args);
            return $instance;
        }
        else {
            return null;
        }
    }

    function create($key, $class_name)
    {
        $args = array_slice(func_get_args(), 1);
        $instance = call_user_func_array(array($this, 'init'), $args);

        $this->register($key, $instance);
        return $this->fetch($key);
    }

    function register($key, $object)
    {
        $this->class_instances[$key] = $object;
    }

    function fetch($key)
    {
        return isset($this->class_instances[$key])
                ? $this->class_instances[$key]
                : null;
    }

    function get_subclass_name($superclass, $subclass)
    {
        $candidate_subclass_names = array($subclass, "$subclass$superclass", "{$subclass}_{$superclass}");
        foreach ($candidate_subclass_names as $class_name) {
            if ( $this->is_subclass($superclass, $class_name) ) {
                $class_metadata = $this->get_class_metadata($class_name);
                return $class_metadata->name; // class name with correct capitalization
            }
        }
        return null;
    }

    /**
     * @return Index
     */
    function get_index()
    {
        return $this->index;
    }

    function load($class_name)
    {
        $class_filepath = $this->get_class_filepath($class_name);

        if (!$class_filepath)
            return;

        require_once $class_filepath;
    }

    function enable_autoload()
    {
        spl_autoload_register(array($this, 'load'));
    }
    
    function is_subclass($superclass, $subclass)
    {
        $subclass_names = $this->get_subclass_names($superclass);
        $subclass_names = array_map('strtolower', $subclass_names);
        return in_array(strtolower($subclass), $subclass_names);
    }

    function get_subclass_names($superclass)
    {
        $superclass_metadata = $this->get_class_metadata($superclass);

        if (!$superclass_metadata)
            return array();

        if (!isset($superclass_metadata->subclasses))
            return array();

        return $superclass_metadata->subclasses;
    }

    /**
     * @param $class_name
     * @return ClassMetadata|null
     */
    function get_class_metadata($class_name)
    {
        $class_name = strtolower($class_name);
        return $this->index->get_metadata("$class_name class");
    }

    private function get_class_filepath($class_name)
    {
        $class_metadata = $this->get_class_metadata($class_name);
        if (!$class_metadata)
            return null;

        $file_metadata = $this->index->get_metadata($class_metadata->file);
        return $file_metadata->filepath;
    }

    private function get_num_constructor_parameters($class)
    {
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (!$constructor)
            return 0;

        $params = $constructor->getParameters();
        return count($params);
    }

}
