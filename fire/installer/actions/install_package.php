<?php

class InstallPackageAction extends Action
{

    /* @var $installer PackageInstaller */
    private $installer;

    function __construct()
    {
        $this->installer = build('package_installer');
    }

    function execute($package_name)
    {
        $this->installer->install($package_name);
        flash::message("Installed package $package_name");

        redirect('admin/packages');
    }

}
