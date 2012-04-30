<?php

class Schema_Tests extends PHPUnit_Framework_TestCase
{

    /**
     * @var Database
     */
    private $db;

    /**
     * @var Database
     */
    private $db2;

    function setUp()
    {
        $this->db = build('database');
        $this->db2 = build('second_database');

        $this->db->destroy_all_tables();
    }

    function test_create_basic_table()
    {
        $db = $this->db;
        $this->assertEquals($db->table('test_table'), null);

        $this->assertTrue(!$db->has_table('test_table'));

        $db->create_table('test_table', array(
            'id' => array('type' => 'id'),
            'foo' => array('type' => 'string'),
        ));

        $this->assertTrue($db->has_table('test_table'));

        $table = $db->table('test_table');
        $this->assertEquals($table->name(), 'test_table');
    }

    function test_create_table_with_missing_column_type()
    {
        $exception_thrown = false;
        $table_name = 'table_with_missing_column_type';
        try {
            $this->db->create_table($table_name, array(
                'id' => array('type' => 'id'),
                'man' => array('type' => 'atypethatdoesntexist'),
            ));
        }
        catch (ColumnTypeMissingException $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown);
    }

    /*
    function test_table_persistance()
    {
        $db = $this->db;
        $db2 = $this->db2;

        $db->create_table('uncached_table', array(
            'id' => array('type' => 'id'),
        ));
        $this->assertTrue($db->table('uncached_table') != null);

        $table = $db2->table('uncached_table', 'table persists between database sessions');
        $this->assertTrue($table != null);
        $this->assertTrue($db2->has_table('uncached_table'));
    }
    */

    function test_destroy_table()
    {
        $db = $this->db;

        $db->create_table('test_table_to_destroy', array(
            'id' => array('type' => 'id'),
        ));


        $db->destroy_table('test_table_to_destroy');

        $test_table = $db->table('test_table_to_destroy');
        $this->assertEquals($test_table, null);
    }

    function test_rename_table()
    {
        $db = $this->db;

        $table = $db->create_table('ven', array(
            'id' => array('type' => 'id'),
        ));

        $this->assertEquals($db->table('ven')->name(), 'ven');
        $this->assertEquals($table, $db->table('ven'), 'tables refer to the same object');

        $db->rename_table('ven', 'vendiddy');

        $this->assertEquals($db->table('ven'), null);
        $this->assertEquals($db->table('vendiddy')->name(), 'vendiddy');
        $this->assertEquals($table->name(), 'vendiddy', 'previously referenced table has updated name');
    }

    function test_create_column()
    {
        $db = $this->db;

        $db->create_table('col_table', array(
            'id' => array('type' => 'id'),
        ));
        $table = $db->table('col_table');
        $this->assertEquals($table->column('new_col'), null, 'column doesnt exist yet');

        $table->create_column('new_col', array('type' => 'string'));
        $column = $table->column('new_col');
        $this->assertEquals($column->name(), 'new_col');
    }

    function test_rename_column()
    {
        $db = $this->db;

        $table = $db->create_table('rename_column_table', array(
            'id' => array('type' => 'id'),
            'orange' => array('type' => 'string'),
        ));

        $column = $table->column('orange');
        $this->assertEquals($column->name(), 'orange');

        $table->rename_column('orange', 'apple');

        $this->assertEquals($table->column('orange'), null);
        $this->assertEquals($table->column('apple'), $column, 'new column name refers to same object');
        $this->assertEquals($column->name(), 'apple', 'name has changed for object');
    }

    function test_destroy_column()
    {
        $db = $this->db;
        $db->create_table('destroy_col_table', array(
            'id' => array('type' => 'id'),
            'temp_col' => array('type' => 'string'),
        ));


        $this->assertEquals($db->table('destroy_col_table')->column('temp_col')->name(), 'temp_col');

        $db->table('destroy_col_table')->destroy_column('temp_col');
        $this->assertEquals($db->table('destroy_col_table')->column('temp_col'), null);
    }

    function test_create_single_column_index()
    {
        $db = $this->db;
        $table = $db->create_table('create_index_table', array(
            'id' => array('type' => 'id'),
            'indexed_column' => array('type' => 'string'),
        ));

        $this->assertTrue(!$table->has_index('indexed_column'), 'no index before you create one');

        $table->create_index('indexed_column');
        $this->assertTrue($table->has_index('indexed_column'), 'index one you create one');

        $table->destroy_index('indexed_column');
        $this->assertTrue(!$table->has_index('indexed_column'), 'no index after you destroy it');
    }

    function test_create_multi_column_index()
    {
        $db = $this->db;
        $table = $db->create_table('create_multi_column_index_table', array(
            'id' => array('type' => 'id'),
            'indexed_column_1' => array('type' => 'string'),
            'indexed_column_2' => array('type' => 'string'),
        ));

        $this->assertTrue(!$table->has_index('indexed_column_1', 'indexed_column_2'), 'no index before you create one');

        $table->create_index('indexed_column_1', 'indexed_column_2');
        $this->assertTrue($table->has_index('indexed_column_1', 'indexed_column_2'), 'index present after you create it');
        $this->assertTrue($table->has_index('indexed_column_2', 'indexed_column_1'), 'column order doesnt matter');

        $table->destroy_index('indexed_column_1', 'indexed_column_2');
        $this->assertTrue(!$table->has_index('indexed_column_1', 'indexed_column_2'), 'no index after you destroy it');
    }

    function test_destroy_column_with_foreign_key()
    {
        $this->db->create_table('coconuts', array(
            'id' => array('type' => 'id'),
        ));

        $this->db->create_table('monkeys', array(
            'id' => array('type' => 'id'),
            'coconut_id' => array('type' => 'integer'),
        ));

        $this->db->table('monkeys')->create_foreign_key('coconut_id', 'coconuts', 'id');
        $this->assertTrue($this->db->table('monkeys')->has_column('coconut_id'));

        $this->db->table('monkeys')->destroy_column('coconut_id');
        $column_destroyed = !$this->db->table('monkeys')->has_column('coconut_id');
        $this->assertTrue($column_destroyed, 'column was destroyed');
    }

    function test_destroy_column_with_index()
    {
        $db = $this->db;
        $table_name = 'table_with_column_with_index';

        $table = $db->create_table($table_name, array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));
        $table->create_index('name');
        $this->assertTrue($table->has_column('name'));
        $this->assertTrue($table->has_index('name'));

        $table->destroy_column('name');
        $this->assertTrue(!$table->has_column('name'));
        $this->assertTrue(!$table->has_index('name'));
    }

    function test_create_foreign_key()
    {
        $db = $this->db;

        $users_table = $db->create_table('users', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $admins_table = $db->create_table('admins', array(
            'id' => array('type' => 'id'),
            'user_id' => array('type' => 'integer'),
        ));

        $this->assertTrue(!$admins_table->has_foreign_key('user_id'));

        $admins_table->create_foreign_key('user_id', 'users', 'id');
        $this->assertTrue($admins_table->has_foreign_key('user_id'));

        $this->assertEquals($admins_table->get_foreign_key_table_name('user_id'), 'users');
        $this->assertEquals($admins_table->get_foreign_key_column_name('user_id'), 'id');

        $admins_table->destroy_foreign_key('user_id');
        $this->assertTrue(!$admins_table->has_foreign_key('user_id'));
    }

    function test_create_unique_index()
    {
        $table = $this->db->create_table('unique_index_table', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
        ));

        $table->create_unique_index('name');
        $this->assertTrue($table->has_index('name'));

        $table->destroy_index('name');
        $this->assertTrue(!$table->has_index('name'));
    }

    function test_create_foreign_key_with_missing_table()
    {
        $admins_table = $this->db->create_table('admins_foo', array(
            'id' => array('type' => 'id'),
            'user_id' => array('type' => 'integer'),
        ));
        $exception_thrown = false;
        try {
            $admins_table->create_foreign_key('user_id', 'fools', 'id');
        }
        catch (TableMissingException $e) {
            $exception_thrown = true;
        }
        $this->assertTrue($exception_thrown, 'exception thrown when table is missing');
    }

    function test_fooo()
    {
        $this->db->create_table('default_valued_table', array(
            'id' => array('type' => 'id'),
            'name' => array('type' => 'string'),
            'age' => array('type' => 'integer', 'default' => 15),
            'count' => array('type' => 'integer', 'default' => 0),
            'token' => array('type' => 'integer'),
        ));

        $row = $this->db->table('default_valued_table')->create_row(array(
            'name' => 'foo',
        ));

        $this->assertSame($row->age, 15, 'default value is applied');
        $this->assertSame($row->count, 0, 'default value of 0 is applied');
        $this->assertNull($row->token, 'no default value is applied');
    }

    /**
     * @expectedException IdColumnMissingException
     */
    function test_id_column_missing()
    {
        $table = $this->db->create_table('table_with_no_id_column', array(
            'name' => array('type' => 'string'),
        ));
        $table->id_column();
    }

}
