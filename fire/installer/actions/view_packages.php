<?php

class ViewPackagesAction extends Action
{

    /* @var $installer PackageInstaller */
    private $installer;

    function __construct()
    {
        $this->installer = build('package_installer');
    }

    function execute()
    {
        $packages = $this->installer->list_packages();
        print r::admin_packages(array(
            'packages' => $packages,
            'installer' => $this->installer,
        ));
    }

}

