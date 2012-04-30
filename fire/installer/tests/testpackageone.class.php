<?php

class TestPackageOne extends Package
{

    public $version = '1.5.2';

    function install()
    {
        $this->db()->create_table('table_one', array(
                                                    'id' => array('type' => 'id'),
                                               ));
        $this->db()->create_table('table_two', array(
                                                    'id' => array('type' => 'id'),
                                               ));
        $this->db()->create_table('table_three', array(
                                                      'id' => array('type' => 'id'),
                                                 ));
    }

    function uninstall()
    {
        $this->db()->destroy_table('table_one');
        $this->db()->destroy_table('table_two');
        $this->db()->destroy_table('table_three');
    }


    function upgrade_to_1_5_2()
    {
        $this->db()->create_table('table_three', array(
                                                      'id' => array('type' => 'id'),
                                                 ));
    }

    function upgrade_to_1_2_1()
    {
        $this->db()->create_table('table_two', array(
                                                    'id' => array('type' => 'id'),
                                               ));
    }

    function upgrade_to_0_5()
    {
        $this->db()->create_table('table_one', array(
                                                    'id' => array('type' => 'id'),
                                               ));
    }

    /**
     * @return Database
     */
    private function db()
    {
        return $this->database;
    }

}
