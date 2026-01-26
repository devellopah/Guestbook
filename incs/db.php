<?php

$db_config = [
    'host' => 'db',
    'user' => 'root',
    'password' => 'password',
    'db_name' => 'guestbook',
];

$db_options = [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

$dsn = "mysql:dbname={$db_config['db_name']};host={$db_config['host']};charset=utf8";

try {
    $db = new PDO($dsn, $db_config['user'], $db_config['password'], $db_options);
} catch (PDOException $e) {
    log_error("Database connection failed: " . $e->getMessage(), [
        'host' => $db_config['host'],
        'db_name' => $db_config['db_name']
    ]);
    throw new DatabaseException("Database service temporarily unavailable");
}
