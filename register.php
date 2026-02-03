<?php

// Load the MVC autoloader
require_once __DIR__ . '/src/autoload.php';

// Initialize the router and dispatch the request
$router = new \Core\Router();
$router->dispatch();
