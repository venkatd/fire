<?php

class PdoDatabaseStatement extends PDOStatement
{

    /**
     * @var $dbh PdoDatabaseHandle
     */
    public $dbh;

    private static $queries = array();

    protected function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    function execute($input_parameters = array())
    {
        $query = $this->normalizeQuery($this->queryString, $input_parameters);

        benchmark::start($query, 'database');
        $result = call_user_func_array(array($this, 'parent::execute'), func_get_args());
        benchmark::end($query, 'database');

        return $result;
    }

    private function normalizeQuery($query)
    {
        return preg_replace('/:\w+/', '?', $query);
    }

}
