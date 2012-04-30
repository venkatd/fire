<?php

define('PACKAGE_STATUS_INACTIVE', 'inactive');
define('PACKAGE_STATUS_INSTALLED', 'installed');

class PackageInstaller
{

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var ClassLoader
     */
    protected $class_loader;

    function __construct(Database $database, ClassLoader $class_loader)
    {
        $this->database = $database;
        $this->class_loader = $class_loader;
        $this->_create_table_if_missing();
    }

    function list_packages()
    {
        return $this->class_loader->get_subclass_names('Package');
    }

    function install($package_name)
    {
        $package_row = $this->get_package_row($package_name);
        if ($package_row) {
            $package = $this->get_package($package_name);
            $package->install();

            $package_row->version = $package->version;
            $package_row->status = PACKAGE_STATUS_INSTALLED;
            $package_row->save();
        }
        else {
            throw new Exception("$package_name doesn't exist");
        }
    }

    function uninstall($package_name)
    {
        $package_row = $this->get_package_row($package_name);
        if ($package_row) {
            /* @var $package Package */
            $package = $this->get_package($package_name);
            $package->uninstall();

            $package_row->status = PACKAGE_STATUS_INACTIVE;
            $package_row->version = $package->version;
            $package_row->save();
        }
    }

    function exists($package)
    {
        $packages = $this->list_packages();
        $packages = array_map('strtolower', $packages);
        return in_array(strtolower($package), $packages);
    }

    function is_installed($package_name)
    {
        if (!$this->exists($package_name))
            return false;

        $package_row = $this->get_package_row($package_name);
        return $package_row->status == PACKAGE_STATUS_INSTALLED;
    }

    function update($package_name)
    {
        $package_row = $this->get_package_row($package_name);
        if ($package_row) {
            $package = $this->get_package($package_name);
            $update_methods = $this->get_update_methods($package);
            
            foreach ($update_methods as $method) {
                $package->$method();
            }
            
            $package_row->version = $package->version;
            $package_row->save();
        }
        else {
            throw new Exception("$package_name doesn't exist");
        }
    }

    function update_to($package_name, $target_version)
    {
        throw new Exception("update_to is not yet implemented");
    }

    function rollback_to($package_version, $target_version)
    {
        throw new Exception("rollback_to is not yet implemented");
    }

    function get_installed_version($package_name)
    {
        $package_row = $this->get_package_row($package_name);
        return $package_row ? $package_row->version : null;
    }

    function get_available_version($package_name)
    {
        $package = $this->get_package($package_name);
        return $package ? $package->version : null;
    }

    /**
     * @param  $package_name
     * @return Package
     */
    function get_package($package_name)
    {
        return $this->class_loader->init_subclass('Package', $package_name, $this->database);
    }

    /**
     * @param  $package_name
     * @return DatabaseRow|null
     */
    function get_package_row($package_name)
    {
        if (!$this->exists($package_name))
            return null;

        if (!$this->table()->row_exists($package_name)) {
            $this->table()->create_row(array(
                                            'name' => $package_name,
                                            'version' => 0,
                                            'status' => 'inactive',
                                       ));
        }

        return $this->table()->row($package_name);
    }

    /**
     * @return DatabaseTable
     */
    private function table()
    {
        return $this->database->table('fire_packages');
    }
    
    private function _create_table_if_missing()
    {
        if (!$this->database->has_table('fire_packages')) {
            $this->database->create_table('fire_packages', array(
                                                                'name' => array('type' => 'key'),
                                                                'version' => array('type' => 'string', 'null' => false, 'default' => ''),
                                                                'status' => array('type' => 'string'),
                                                           ));
        }
    }

    private function get_update_methods(Package $package)
    {
        $update_methods = array();

        $all_update_methods = $this->get_all_update_methods($package);

        $current_version = $this->get_installed_version(get_class($package));
        $target_version = $this->get_available_version(get_class($package));

        foreach ($all_update_methods as $update_method) {
            $update_method_version = $this->get_update_method_version($update_method);
            if (version_compare($update_method_version, $current_version, '>')
                && version_compare($update_method_version, $target_version, '<=')
            ) {
                $update_methods[] = $update_method;
            }
        }

        return $update_methods;
    }

    private function get_all_update_methods(Package $package)
    {
        $update_methods = array();
        $all_update_methods = get_class_methods($package);

        foreach ($all_update_methods as $method) {
            if ($this->is_update_method($method)) {
                $update_methods[] = $method;
            }
        }

        usort($update_methods, array($this, 'update_method_compare'));

        return $update_methods;
    }

    private function get_versions(Package $package)
    {
        $versions = array();

        $methods = get_class_methods($package);
        foreach ($methods as $method) {
            if ($this->is_update_method($method)) {
                $versions[] = $this->get_update_method_version($method);
            }
        }
        usort($versions, 'version_compare');
        return $versions;
    }

    private function update_method_compare($a, $b)
    {
        return version_compare(
            $this->get_update_method_version($a),
            $this->get_update_method_version($b)
        );
    }

    private function is_update_method($method)
    {
        return preg_match('/^update_.+/', $method) == 1;
    }

    private function get_update_method_version($method)
    {
        $method = preg_replace('/^update_/', '', $method);
        $method = preg_replace('/\D+/', '.', $method);
        return $method;
    }
    
}
