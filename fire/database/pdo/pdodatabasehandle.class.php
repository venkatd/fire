<?php

class PdoDatabaseHandle extends PDO
{

    function __construct($dsn)
    {
        $args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $args);

        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PdoDatabaseStatement', array($this)));
    }

}
