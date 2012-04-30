<?php

class SessionPackage extends Package
{

    public $version = '0.2';

    function install()
    {
        $this->database->create_table('sessions', array(
            'id' => array('type' => 'key'),
            'created' => array('type' => 'time'),
            'updated' => array('type' => 'time'),
            'data' => array('type' => 'text'),
        ));
    }

}
