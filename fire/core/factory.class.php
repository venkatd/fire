<?php

class Factory
{

    /**
     * @var ConfigSource
     */
    protected $config_source;

    /**
     * @var ClassLoader
     */
    protected $class_loader;

    protected $config;

    function __construct(ConfigSource $config_source, ClassLoader $class_loader)
    {
        $this->config_source = $config_source;
        $this->class_loader = $class_loader;

        $this->class_loader->register('config_source', $config_source);
        $this->class_loader->register('class_loader', $class_loader);

        $this->config = $this->config_source->load();
    }

    function build($key)
    {
        if (!$this->class_loader->fetch($key) && isset($this->config[$key])) {
            $class_config = $this->config[$key];
            $class = $class_config['type'];

            $args = $this->get_constructor_arguments($class, $class_config);
            $args = array_merge(array($key, $class), $args);
            call_user_func_array(array($this->class_loader, 'create'), $args);
        }

        //factory exists for the item
        elseif (isset($this->config[$key . '_factory'])) {
            $specialized_factory = $this->build($key . '_factory');
            $args = array_slice(func_get_args(), 1);
            $instance = call_user_func_array(array($specialized_factory, 'build'), $args);
            $this->class_loader->register($key, $instance);
        }

        return $this->class_loader->fetch($key);
    }

    function register($key, $object)
    {
        $this->class_loader->register($key, $object);
    }

    private function get_constructor_arguments($class, $config)
    {
        $args = array();

        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if (!$constructor)
            return $args;

        $params = $constructor->getParameters();

        if (count($params) == 1 && $params[0]->getName() == 'options') {
            $args[0] = $config;
            return $args;
        }

        /* @var $param ReflectionParameter */
        foreach ($params as $param) {
            $arg_class = $param->getClass();
            $arg_position = $param->getPosition();
            $arg_name = $param->getName();

            if (isset($config[$arg_name])) {
                $arg_value = $config[$arg_name];
                if ($arg_class)
                    $arg_value = $this->build($arg_value);
            }
            else {
                $arg_value = $param->getDefaultValue();
            }

            $args[$arg_position] = $arg_value;
        }

        return $args;
    }

}
