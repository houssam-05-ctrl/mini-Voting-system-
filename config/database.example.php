<?php


define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'voting_system');
define('DB_USER',    getenv('DB_USER')    ?: 'YOUR_DB_USER'); // if you want to make it yours you can add your username here :))  
define('DB_PASS',    getenv('DB_PASS')    ?: 'YOUR_DB_PASS');  // and your password here 
define('DB_CHARSET', 'utf8mb4');

define('SESSION_LIFETIME', 3600);
define('SESSION_NAME',     'voting_sid');
define('CHAIN_GENESIS_HASH', str_repeat('0', 64));
