<?php
namespace Config;
class Config{
    static function getConfig($key, $default = null){ 
        $config = require_once 'env.php';
        return $config[$key] ?? $default;
    }
}