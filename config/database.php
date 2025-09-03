<?php
require_once __DIR__ . '/config.php';

use Config\Config;

return [
    'host'     => Config::getConfig('DB_HOST', 'localhost'),
    'username' => Config::getConfig('DB_USER', 'root'),
    'password' => Config::getConfig('DB_PASS', null),
    'database' => Config::getConfig('DB_NAME', 'db_tuition'),
];