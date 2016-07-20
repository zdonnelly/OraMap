<?php

require_once '../config.php';

session_start();

if($_SERVER['SERVER_NAME'] == 'localhost') {
    $_SESSION['ENV'] = 'development';
}
else {
    $_SESSION['ENV'] = 'production';
}

$_SESSION['DBUSER'] = Config::getDBUser($_SESSION['ENV']);
$_SESSION['DBPASS'] = Config::getDBPassword($_SESSION['ENV']);
$_SESSION['DBDSN']  = Config::getDBDsn($_SESSION['ENV']);
