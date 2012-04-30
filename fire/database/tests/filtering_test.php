<?php

require_once 'PHPUnit/Autoload.php';

class Filtering_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @var Database
     */
    private $db;

    function setUp()
    {
        $this->db = build('database');

        $this->db->destroy_all_tables();

        $cities_table = $this->db->create_table('cities', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $dc = $cities_table->create_row(array(
            'name' => 'dc',
        ));
        $ny = $cities_table->create_row(array(
            'name' => 'ny',
        ));

        $users_table = $this->db->create_table('users', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
            'city_id' => array('type' => 'integer'),
        ));
        $users_table->create_foreign_key('city_id', 'cities', 'id');

        $bob = $users_table->create_row(array('name' => 'bob', 'city_id' => $dc->id));
        $joe = $users_table->create_row(array('name' => 'joe', 'city_id' => $ny->id));

        $food_table = $this->db->create_table('food', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
            'purchased' => array('type' => 'date'),
            'type' => array('type' => 'string'),
            'owner_id' => array('type' => 'integer'),
        ));
        $food_table->create_foreign_key('owner_id', 'users', 'id');

        $food_table->create_row(array(
            'name' => 'apple',
            'purchased' => new DateTime('2011-12-07'),
            'type' => 'fruit',
            'owner_id' => $bob->id,
        ));

        $food_table->create_row(array(
            'name' => 'carrot',
            'purchased' => new DateTime('2011-08-23'),
            'type' => 'vegetable',
            'owner_id' => $bob->id,
        ));

        $food_table->create_row(array(
            'name' => 'orange',
            'purchased' => new DateTime('2011-12-07'),
            'type' => 'fruit',
            'owner_id' => $joe->id,
        ));

        $food_table->create_row(array(
            'name' => 'kiwi',
            'purchased' => new DateTime('2011-12-08'),
            'type' => 'fruit',
        ));

        $food_table->create_row(array(
            'name' => 'celery',
            'purchased' => new DateTime('2011-12-07'),
            'type' => 'vegetable',
        ));

        $food_table->create_row(array(
            'name' => 'cyanide',
            'purchased' => new DateTime('2009-12-07'),
            'type' => 'chemical',
        ));

        $this->db->create_table('dates', array(
            'id' => array('type' => 'id'),
            'year' => array('type' => 'integer'),
            'month' => array('type' => 'integer'),
        ));
    }


    function test_basic_where()
    {
        $food = array();
        $oranges = $this->db->table('food')->where('name', 'orange');
        foreach ($oranges as $id => $orange) {
            $food[] = $orange->name;
        }
        $this->assertEquals($food[0], 'orange');
    }

    function test_date_where()
    {
        $items = array();
        $seventh = new DateTime('2011-12-07');
        $query = $this->db->table('food')->where('purchased', $seventh);
        foreach ($query as $id => $item) {
            $items[] = $item->name;
        }

        $this->assertEquals(implode(',', $items), 'apple,orange,celery');
    }

    function test_count()
    {
        $query = $this->db->table('food')->where('type', 'fruit');
        $this->assertEquals($query->count(), 3);
    }

    function test_multi_where()
    {
        $seventh = new DateTime('2011-12-07');
        $item = $this->db->table('food')
                ->where('purchased', $seventh)
                ->where('type', 'vegetable')
                ->first();
        $this->assertEquals($item->name, 'celery');
    }

    function test_delete()
    {
        $cyanide = $this->db->table('food')
                ->where('type', 'chemical')
                ->first();

        $this->assertEquals($cyanide->name, 'cyanide');

        $this->db->table('food')
                ->where('type', 'chemical')
                ->destroy();

        $cyanide_again = $this->db->table('food')
                ->where('type', 'chemical')
                ->first();

        $this->assertEquals($cyanide_again, null);
    }

    function test_multi_table_filter()
    {
        $bobs_fruits = $this->db->table('food')
                                ->where('owner.name', 'bob');
        $this->assertEquals($bobs_fruits->count(), 2);
    }

    function test_complex_multi_table_filter()
    {
        $ny_bob_fruits = $this->db->table('food')
                            ->where('owner.name', 'bob')
                            ->where('owner.city.name', 'ny');
        $this->assertEquals($ny_bob_fruits->count(), 0);

        $dc_bob_fruits = $this->db->table('food')
                ->where('owner.name', 'bob')
                ->where('owner.city.name', 'dc');
        $this->assertEquals($dc_bob_fruits->count(), 2);
    }

    function test_order_by_related()
    {
        $users = $this->db->table('users')
                          ->order_by('city.name', 'desc');
        $this->assertEquals($users->count(), 2);

        $users = $users->to_array();

        $this->assertEquals($users[0]->name, 'joe');
        $this->assertEquals($users[1]->name, 'bob');
    }

    function test_where_in()
    {
        $celery_kiwi = $this->db->table('food')
                                ->where('name', array('celery', 'kiwi'));
        $result = array();
        foreach ($celery_kiwi as $item) {
            $result[] = $item->name;
        }
        sort($result);

        $this->assertEquals('celery,kiwi', implode(',', $result));
    }

    function test_multiple_order_by()
    {
        $dates_table = $this->db->table('dates');

        $dates_table->create_row(array('year' => 2012, 'month' => 9));
        $dates_table->create_row(array('year' => 2010, 'month' => 8));
        $dates_table->create_row(array('year' => 2012, 'month' => 7));
        $dates_table->create_row(array('year' => 2009, 'month' => 12));

        $dates = $dates_table->order_by(array('year' => 'asc', 'month' => 'asc'))->to_array();
        $this->assertEquals(2009, $dates[0]->year);
        $this->assertEquals(2010, $dates[1]->year);
        $this->assertEquals(2012, $dates[2]->year);
        $this->assertEquals(7, $dates[2]->month);
        $this->assertEquals(2012, $dates[3]->year);
        $this->assertEquals(9, $dates[3]->month);

        $dates = $dates_table->order_by(array('year' => 'asc', 'month' => 'desc'))->to_array();
        $this->assertEquals(2009, $dates[0]->year);
        $this->assertEquals(2010, $dates[1]->year);
        $this->assertEquals(2012, $dates[2]->year);
        $this->assertEquals(9, $dates[2]->month);
        $this->assertEquals(2012, $dates[3]->year);
        $this->assertEquals(7, $dates[3]->month);
    }

}
