<?php

class Crud_Tests extends PHPUnit_Framework_TestCase
{

    /**
     * @var Database
     */
    private $db;

    function setUp()
    {
        $this->db = build('database');
        $this->db->destroy_all_tables();

        $this->db->create_table('data', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $this->db->create_table('unique_data', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
            'school' => array('type' => 'string'),
            'credits' => array('type' => 'integer'),
        ));
        $this->db->table('unique_data')->create_unique_index('name', 'school');

        $this->db->create_table('no_autoinc', array(
            'id' => array('type' => 'id', 'auto_increment' => false),
            'name' => array('type' => 'string'),
        ));

        $this->db->create_table('hash', array(
            'id' => array('type' => 'key'),
            'name' => array('type' => 'string'),
        ));
    }

    function test_insert()
    {
        $table = $this->db->table('data');
        $this->assertEquals($table->row(1), null, 'row doesnt exist');

        $this->assertEquals($table->count(), 0, 'zero rows in db');

        $row = $table->create_row(array('name' => 'ven'));
        $this->assertEquals($table->row($row->id), $row, 'same object is referenced');
        $this->assertEquals($row->name, 'ven', 'row data was properly saved');

        $this->assertEquals($table->count(), 1, '1 row in db');

        $second_row = $table->create_row(array('name' => 'bob'));
        $this->assertEquals($table->count(), 2, '2 rows in db');

        $this->assertTrue(is_int($row->id));
        $this->assertTrue(is_string($row->name));
    }

    function test_update()
    {
        $table = $this->db->table('data');
        $row = $table->create_row(array('name' => 'antelope'));

        $this->assertEquals($row->name, 'antelope');

        $row->name = 'koala';
        $row->save();

        $this->assertEquals($table->row($row->id)->name, 'koala');
    }

    function test_destroy()
    {
        $table = $this->db->table('data');

        $row1 = $table->create_row(array('name' => 'row1'));
        $row2 = $table->create_row(array('name' => 'row2'));

        $row1_id = $row1->id;
        $row2_id = $row2->id;

        $table->destroy_row($row1_id);
        $this->assertEquals($table->row($row1_id), null, 'deleted row is null');
        $this->assertEquals($table->row($row2_id), $row2, 'regular row is still there');
    }

    function test_create_row_with_time()
    {
        $this->db->table('data')->create_column('my_date', array('type' => 'time'));
        $date = new XDateTime('2011-12-02', new DateTimeZone('UTC'));
        $row = $this->db->table('data')->create_row(array(
            'name' => 'woo a date name',
            'my_date' => $date,
        ));

        $same_date = clone $date;
        $this->assertEquals($row->my_date, $same_date);

        $row->my_date = null;
        $row->save();

        $this->assertEquals($row->my_date, null);

        $row->my_date = $date;
        $row->save();
        $this->assertEquals($row->my_date, $same_date);
    }

    function test_create_or_update_row()
    {
        $table = $this->db->table('data');

        $row_a = $table->create_row(array('name' => 'row a'));

        $updated_row = $table->create_or_update_row(array('id' => $row_a->id, 'name' => 'woo'));
        $this->assertEquals($row_a->name, 'woo', 'row was successfully updated');
        $this->assertTrue($updated_row == $row_a, 'correct row was updated');

        $row_b = $table->create_or_update_row(array(
            'name' => 'row b',
        ));

        $this->assertEquals($row_b->name, 'row b', 'row was successfully created');

        $this->assertTrue($row_a->id != $row_b->id);
    }

    function test_create_or_update_with_unique_table()
    {
        $table = $this->db->table('unique_data');
        $row = $table->create_or_update_row(array(
            'name' => 'ven',
            'school' => 'md',
            'credits' => 90,
        ));
        $this->assertEquals($row->name, 'ven');
        $this->assertEquals($row->credits, 90);

        $updated_row = $table->create_or_update_row(array(
            'name' => 'ven',
            'school' => 'md',
            'credits' => 94,
        ));
        $this->assertEquals($updated_row, $row, 'same row is updated');
        $this->assertEquals($row->credits, 94);
        $this->assertEquals($updated_row->credits, 94);
    }

    function test_create_or_update_with_non_autoincrement_table()
    {
        $table = $this->db->table('no_autoinc');

        $row = $table->create_or_update_row(array(
            'id' => 5,
            'name' => 'ven',
        ));

        $same_row = $table->create_or_update_row(array(
            'id' => 5,
            'name' => 'venicii',
        ));

        $this->assertEquals($row, $same_row, 'the updated row references the same object');
        $this->assertEquals($row->name, 'venicii', 'row field was properly updated');
    }

    function test_get_row_from_keyed_table_with_data_present()
    {
        $this->db->execute("INSERT INTO hash (id, name) VALUES ('aaa', 'bbb')");

        $row = $this->db->table('hash')->row('aaa');
        $this->assertEquals('bbb', $row->name);
    }

    function test_crud_with_keyed_table()
    {
        $this->db->table('hash')->create_row(array(
            'id' => 'foo',
            'name' => 'keg',
        ));

        $row = $this->db->table('hash')->row('foo');

        $this->assertEquals('keg', $row->name);

        $row->name = 'waa';
        $row->save();

        $this->assertEquals('waa', $row->name);
    }

}
