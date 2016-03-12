<?php

// $dbType = "local";
$dbType = 'new';

switch($dbType) {

    case 'local':
        define('DB_NAME', 'bc_sitelok');
        define('DB_CALC_NAME', 'calculations');
        define('DB_SITELOK_NAME', 'shared');
        define('DB_SHARED_NAME', 'shared');
        define('DB_HOSTNAME', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        break;
    case 'local2':
        define('DB_NAME', 'bc');
        define('DB_CALC_NAME', 'calculations');
        define('DB_SITELOK_NAME', 'shared');
        define('DB_SHARED_NAME', 'shared');
        define('DB_HOSTNAME', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', 'root');
        break;
    case 'new':
        define('DB_NAME', 'bc_sitelok');
        define('DB_CALC_NAME', 'calculations_new');
        define('DB_SITELOK_NAME', 'shared');
        define('DB_SHARED_NAME', 'shared');
        define('DB_HOSTNAME', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        break;
}