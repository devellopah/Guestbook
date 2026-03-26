<?php

// Start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize error handling
\Core\ErrorHandler::init();

// Set debug mode based on environment
if (getenv('APP_ENV') === 'development') {
  define('DEBUG_MODE', true);
} else {
  define('DEBUG_MODE', false);
}

// Initialize application with DI container
$app = \Core\Application::getInstance();
$container = $app->getContainer();

// Make container available globally for API controllers
$GLOBALS['container'] = $container;

$app->run();
