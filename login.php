<?php

session_start();

// Global Exception Handler
set_exception_handler(function ($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    http_response_code(500);
    echo "<h1>System Error</h1><p>We're experiencing technical difficulties. Please try again later.</p>";
    die();
});

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Safe file loading with exception handling
try {
    if (!safe_require(__DIR__ . '/vendor/autoload.php')) {
        throw new Exception("Composer autoloader not found");
    }
    if (!safe_require(__DIR__ . '/incs/db.php')) {
        throw new Exception("Database configuration not found");
    }
    if (!safe_require(__DIR__ . '/incs/functions.php')) {
        throw new Exception("Core functions not found");
    }
} catch (Exception $e) {
    log_error("Application startup error: " . $e->getMessage());
    die("Application cannot start due to configuration errors.");
}

if (check_auth()) {
    redirect('index.php');
}

$title = 'Login';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!check_rate_limit('login_attempt', 5, 300)) {
        $remaining_time = get_rate_limit_remaining_time('login_attempt');
        $_SESSION['errors'] = "Too many login attempts. Please wait " . ceil($remaining_time / 60) . " minutes before trying again.";
    } else if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errors'] = 'Security validation failed';
    } else {
        $data = load(['email', 'password']);

        $v = new \Valitron\Validator($data);
        $v->rules([
            'required' => ['email', 'password'],
            'email' => ['email'],
        ]);

        if ($v->validate()) {
            if (login($data)) {
                redirect('index.php');
            } else {
                redirect('login.php');
            }
        } else {
            $_SESSION['errors'] = get_errors($v->errors());
        }
    }
}

require_once __DIR__ . '/views/login.tpl.php';
