<?php

abstract class DatabaseColumn
{

    /**
     * @var \DatabaseTable
     */
    private $table;
    protected $options = array();
    protected $required_options = array('name', 'type');

    function __construct(DatabaseTable $table, $options)
    {
        $this->table = $table;
        $this->options = $options;

        check_required_options($options, $this->required_options);
    }

    function name()
    {
        return $this->options['name'];
    }

    /**
     * @return DatabaseTable
     */
    function table()
    {
        return $this->table;
    }

    abstract function from_database_value($value);

    abstract function to_database_value($value);

    /**
     * @return bool
     */
    function is_primary_key()
    {
        return $this->options['primary_key'];
    }

    /**
     * @return bool
     */
    function auto_increment()
    {
        return isset($this->options['auto_increment'])
             && $this->options['auto_increment'] == true;
    }

    function _set_name($name)
    {
        $this->options['name'] = $name;
    }

}
