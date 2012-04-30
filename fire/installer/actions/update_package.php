<?php

class UpdatePackageAction extends Action
{

    /* @var $installer PackageInstaller */
    private $installer;

    function __construct()
    {
        $this->installer = build('package_installer');
    }

    function execute($package_name)
    {
        $version = $this->installer->get_available_version($package_name);
        $this->installer->update($package_name);
        flash::message("Updated $package_name to $version");

        redirect('admin/packages');
    }

}
