<?php

class Reference_Tests extends PHPUnit_Framework_TestCase
{
    /**
     * @var Database
     */
    private $db;

    function setUp()
    {
        $this->db = factory()->build('database');
        $this->db->destroy_all_tables();

        $this->createTables();
        $this->seedData();
    }

    function createTables()
    {
        $this->db->create_table('users', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $this->db->create_table('events', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $this->db->create_table('checkins', array(
            'id' => array('type' => 'id'),
            'user_id' => array('type' => 'integer'),
            'event_id' => array('type' => 'integer'),
        ));
        $this->db->table('checkins')->create_foreign_key('user_id', 'users', 'id');
        $this->db->table('checkins')->create_foreign_key('event_id', 'events', 'id');

        $this->db->create_table('networks', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $this->db->create_table('user_networks', array(
            'id' => array('type' => 'id'),
            'user_id' => array('type' => 'integer'),
            'network_id' => array('type' => 'integer'),
        ));
        $this->db->table('user_networks')->create_foreign_key('user_id', 'users', 'id');
        $this->db->table('user_networks')->create_foreign_key('network_id', 'networks', 'id');

        $this->db->create_table('user_friends', array(
            'id' => array('type' => 'id'),
            'user_id' => array('type' => 'integer'),
            'friend_id' => array('type' => 'integer'),
        ));
        $this->db->table('user_friends')->create_foreign_key('user_id', 'users', 'id');
        $this->db->table('user_friends')->create_foreign_key('friend_id', 'users', 'id');
    }

    function seedData()
    {
        $this->venkat = $this->db->table('users')->create_row(array(
            'name' => 'venkat',
        ));

        $this->dan = $this->db->table('users')->create_row(array(
            'name' => 'dan',
        ));

        $this->kate = $this->db->table('users')->create_row(array(
            'name' => 'kate',
        ));

        $this->dustin = $this->db->table('users')->create_row(array(
            'name' => 'dustin',
        ));

        $this->mcfaddens = $this->db->table('events')->create_row(array(
            'name' => 'mcfaddens',
        ));

        $this->shadowroom = $this->db->table('events')->create_row(array(
            'name' => 'shadowroom',
        ));

        $this->public = $this->db->table('events')->create_row(array(
            'name' => 'public',
        ));

        $this->venkat_mcfaddens_checkin = $this->db->table('checkins')->create_row(array(
            'user_id' => $this->venkat->id,
            'event_id' => $this->mcfaddens->id,
        ));

        $this->venkat_public_checkin = $this->db->table('checkins')->create_row(array(
            'user_id' => $this->venkat->id,
            'event_id' => $this->public->id,
        ));

        $this->stanford = $this->db->table('networks')->create_row(array(
            'name' => 'stanford',
        ));

        $this->maryland = $this->db->table('networks')->create_row(array(
            'name' => 'maryland',
        ));

        $this->gwu = $this->db->table('networks')->create_row(array(
            'name' => 'gwu',
        ));

        $this->cornell = $this->db->table('networks')->create_row(array(
            'name' => 'cornell',
        ));

        $this->db->table('user_networks')->create_row(array(
            'user_id' => $this->venkat->id,
            'network_id' => $this->stanford->id,
        ));

        $this->db->table('user_networks')->create_row(array(
            'user_id' => $this->venkat->id,
            'network_id' => $this->maryland->id,
        ));

        $this->db->table('user_networks')->create_row(array(
            'user_id' => $this->dan->id,
            'network_id' => $this->stanford->id,
        ));

        $this->db->table('user_networks')->create_row(array(
            'user_id' => $this->dan->id,
            'network_id' => $this->cornell->id,
        ));

        $this->db->table('user_networks')->create_row(array(
            'user_id' => $this->dan->id,
            'network_id' => $this->gwu->id,
        ));

        $this->db->table('user_friends')->create_row(array(
            'user_id' => $this->venkat->id,
            'friend_id' => $this->dan->id,
        ));

        $this->db->table('user_friends')->create_row(array(
            'user_id' => $this->venkat->id,
            'friend_id' => $this->kate->id,
        ));
    }

    function test_local_foreign_key()
    {
        $this->assertEquals($this->venkat_mcfaddens_checkin->user->name, 'venkat');
        $this->assertEquals($this->venkat_mcfaddens_checkin->event->name, 'mcfaddens');
    }

    function test_foreign_key_by_table_name()
    {
        $names = array();
        foreach ($this->venkat->checkins as $checkin) {
            $names[] = $checkin->event->name;
        }
        sort($names);
        $this->assertEquals(implode(',', $names), 'mcfaddens,public');
    }

    function test_foreign_key_with_joining_table()
    {
        $venkats_networks = array();
        foreach ($this->venkat->networks as $network) {
            $venkats_networks[] = $network->name;
        }
        sort($venkats_networks);
        $this->assertEquals(implode(',', $venkats_networks), 'maryland,stanford');
    }

    function test_foreign_key_with_self_join_table()
    {
        $venkat_friends = array();
        foreach ($this->venkat->friends as $friend) {
            $venkat_friends[] = $friend->name;
        }
        sort($venkat_friends);
        $this->assertEquals(implode(',', $venkat_friends), 'dan,kate');
    }

}
