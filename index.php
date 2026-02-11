<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load legacy functions for backward compatibility
require_once __DIR__ . '/incs/functions.php';

// Initialize the router and dispatch the request
$router = new \Core\Router();
$router->dispatch();
