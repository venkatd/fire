<?php

define('FIREPATH', dirname(dirname(__FILE__)) . '/');
define('APPPATH', dirname(FIREPATH) . '/');

require_once FIREPATH . 'debug/krumo.class.php';

require_once FIREPATH . 'core/benchmark.class.php';
require_once FIREPATH . 'core/core.functions.php';

function boot()
{
    factory();
}
