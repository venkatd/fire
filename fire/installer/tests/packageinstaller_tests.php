<?php

define('TEST_PACKAGE_ONE', 'TestPackageOne');
define('TEST_PACKAGE_TWO', 'TestPackageTwo');

class PackageInstaller_Tests extends PHPUnit_Framework_TestCase
{


    /**
     * @var Database
     */
    private $db;

    /**
     * @var PackageInstaller
     */
    private $installer;

    function setUp()
    {
        $this->db = build('database');
        $this->db->destroy_all_tables();
        $this->installer = build('package_installer');
    }

    function test_list_packages()
    {
        $packages = $this->installer->list_packages();
        $this->assertTrue(in_array(TEST_PACKAGE_ONE, $packages));
        $this->assertTrue(in_array(TEST_PACKAGE_TWO, $packages));
    }

    function test_basic_install()
    {
        $this->assertTrue(!$this->installer->is_installed(TEST_PACKAGE_ONE), 'package isnt installed beforehand');

        $this->installer->install(TEST_PACKAGE_ONE);
        $this->assertTrue($this->installer->is_installed(TEST_PACKAGE_ONE), 'package IS installed afterword');
        $this->assertTrue($this->db->has_table('table_one'), 'table has been successfully created');
        $this->assertEquals($this->installer->get_installed_version(TEST_PACKAGE_ONE), '1.5.2');
        $this->assertTrue(!$this->installer->is_installed(TEST_PACKAGE_TWO), 'other package still isnt installed');

        $this->installer->install(TEST_PACKAGE_TWO);
        $this->assertTrue($this->installer->is_installed(TEST_PACKAGE_TWO), 'other package is now installed');

        $this->installer->uninstall(TEST_PACKAGE_ONE);
        $this->assertTrue(!$this->installer->is_installed(TEST_PACKAGE_ONE), ' first package got uninstalled');
        $this->assertTrue(!$this->db->has_table('table_one'), 'table has been successfully destroyed');
        $this->assertTrue($this->installer->is_installed(TEST_PACKAGE_TWO), 'second package is still installed');

        $this->installer->uninstall(TEST_PACKAGE_TWO);
        $this->assertTrue(!$this->installer->is_installed(TEST_PACKAGE_TWO), 'other package got uninstalled');
    }

    function test_available_version()
    {
        $package_one_version = $this->installer->get_available_version(TEST_PACKAGE_ONE);
        $package_two_version = $this->installer->get_available_version(TEST_PACKAGE_TWO);
        
        $this->assertEquals($package_one_version, '1.5.2');
        $this->assertEquals($package_two_version, '1.2.1');
    }
    
}
