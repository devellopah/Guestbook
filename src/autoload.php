<?php

// Autoload classes from the src directory
spl_autoload_register(function ($class) {
  // Convert namespace to file path
  $class = str_replace('\\', '/', $class);
  $file = __DIR__ . "/{$class}.php";

  if (file_exists($file)) {
    require_once $file;
  }
});

// Load the existing functions file for backward compatibility
require_once __DIR__ . '/../incs/functions.php';
