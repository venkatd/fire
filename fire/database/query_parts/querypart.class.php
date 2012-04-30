<?php

abstract class QueryPart
{
    
    /**
     * @return DatabaseTableJoin[]
     */
    function joins()
    {
        return array();
    }

    /**
     * @return array
     */
    function parameters()
    {
        return array();
    }

    /**
     * @abstract
     * @return strings
     */
    abstract function to_sql();
}
