<?php
namespace OraMap;

class Config {

    private static $config;
    function __construct() {
        self::$config = require_once '../config.php';
        echo self::$config;
        die();
    }

    public static function getDBUser($name) {
        foreach(self::$config['dbConnections'] as $env => $info) {
            if($env === $name) {
                return $info['info']['username'];
            }
        }
    }

    public static function getDBPassword($name) {
        foreach(self::$config['dbConnections'] as $env => $info) {
            if($env === $name) {
                return $info['info']['password'];
            }
        }
    }

    public static function getDBDsn($name) {
        foreach(self::$config['dbConnections'] as $env => $info) {
            if($env === $name) {
                return $info['info']['dsn'];
            }
        }
    }
}

