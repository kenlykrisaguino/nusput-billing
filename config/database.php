<?php

use Config\Config;

return [
    'host'     => Config::getConfig('DB_HOST'),
    'username' => Config::getConfig('DB_USER'),
    'password' => Config::getConfig('DB_PASS'),
    'database' => Config::getConfig('DB_NAME'),
];