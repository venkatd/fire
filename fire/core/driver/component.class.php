<?php


class Component
{

    /**
     * @var Driver
     */
    protected $driver;
    protected $options;

    function __construct($options = array())
    {
        $this->options = $options;
        $this->load_driver();
    }

    private $name;
    function name()
    {
        if (!$this->name) {
            $this->name = strtolower(get_class($this));
        }
        return $this->name;
    }

    /**
     * @param string $preset
     * @return Driver
     */
    function driver()
    {
        return $this->driver;
    }

    private function load_driver()
    {
        if ( ! isset($this->driver) ) {
            $driver_name = $this->options['driver'];
            if (is_object($driver_name)) {
                $this->driver = $driver_name;
            }
            else {
                $component_name = strtolower(get_class($this));
                $driver_class_name = "{$driver_name}{$component_name}driver";
                $this->driver = new $driver_class_name($this->options);
            }
        }
    }

}
