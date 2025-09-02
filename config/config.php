<?php
namespace Config;
class Config{
    static function getConfig($key) {
        $config = require_once 'env.php';
        return $config[$key] ?? null;
    }
}