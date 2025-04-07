<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 서버 설정
define('MC_SERVER', $_ENV['MC_SERVER']);
define('MC_PORT', $_ENV['MC_PORT']);

// 데이터베이스 설정
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);

// 캐시 설정
define('PLAYER_CACHE_DURATION', $_ENV['PLAYER_CACHE_DURATION']);
define('LIST_CACHE_DURATION', $_ENV['LIST_CACHE_DURATION']);

// 수집 설정
define('MAX_COLLECTION_TIME', $_ENV['MAX_COLLECTION_TIME']);
define('MAX_BATCH_ATTEMPTS', $_ENV['MAX_BATCH_ATTEMPTS']);
define('INITIAL_RETRY_DELAY', $_ENV['INITIAL_RETRY_DELAY']);
define('MAX_RETRY_DELAY', $_ENV['MAX_RETRY_DELAY']);

// API 설정
define('MOJANG_API_URL', $_ENV['MOJANG_API_URL']);
define('DEFAULT_STEVE_UUID', $_ENV['DEFAULT_STEVE_UUID']); 