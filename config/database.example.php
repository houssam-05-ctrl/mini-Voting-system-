<?php

// =============================================================
//  COPY this file to database.php and fill in your values
//  cp config/database.example.php config/database.php
// =============================================================

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'voting_system');
define('DB_USER',    getenv('DB_USER')    ?: 'YOUR_DB_USER');   // ← à remplir
define('DB_PASS',    getenv('DB_PASS')    ?: 'YOUR_DB_PASS');   // ← à remplir
define('DB_CHARSET', 'utf8mb4');

define('SESSION_LIFETIME', 3600);
define('SESSION_NAME',     'voting_sid');
define('CHAIN_GENESIS_HASH', str_repeat('0', 64));
