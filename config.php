<?php

class Config {

    private static $dbConnections = array(
        'development' => array(
            'info' => array(
                'username' => 'root',
                'password' => '',
                'dsn' => '(description=(address=(protocol=tcp)(host=localhost)(port=1521))(connect_data=(sid=xe)))'
            )
        )
    );

    public static function getDBUser($name) {
        foreach(self::$dbConnections as $env => $info) {
            if($env === $name) {
                return $info['info']['username'];
            }
        }
    }

    public static function getDBPassword($name) {
        foreach(self::$dbConnections as $env => $info) {
            if($env === $name) {
                return $info['info']['password'];
            }
        }
    }
    
    public static function getDBDsn($name) {
        foreach(self::$dbConnections as $env => $info) {
            if($env === $name) {
                return $info['info']['dsn'];
            }
        }
    }
}