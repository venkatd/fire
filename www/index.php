<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '../fire/core/boot.php';

$app = build('app');
$app->trigger('boot');

route_uri_request();
