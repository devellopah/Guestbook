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
    if (!safe_require(__DIR__ . '/incs/Pagination.php')) {
        throw new Exception("Pagination class not found");
    }
} catch (Exception $e) {
    log_error("Application startup error: " . $e->getMessage());
    die("Application cannot start due to configuration errors.");
}

$title = 'Home';

/** @var PDO $db */

if (isset($_POST['send-message'])) {
    if (!check_rate_limit('message_post', 3, 60)) {
        $remaining_time = get_rate_limit_remaining_time('message_post');
        $_SESSION['errors'] = "Too many message submissions. Please wait " . ceil($remaining_time / 60) . " minutes before trying again.";
    } else if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errors'] = 'Security validation failed';
    } else {
        $data = load(['message']);
        $v = new \Valitron\Validator($data);
        $v->rules([
            'required' => ['message'],
        ]);

        if ($v->validate()) {
            if (save_message($data)) {
                redirect('index.php');
            }
        } else {
            $_SESSION['errors'] = get_errors($v->errors());
        }
    }
}

if (isset($_POST['edit-message'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['errors'] = 'Security validation failed';
    } else {
        $data = load(['message', 'id', 'page']);
        $v = new \Valitron\Validator($data);
        $v->rules([
            'required' => ['message', 'id'],
            'integer' => ['id', 'page'],
        ]);

        if ($v->validate()) {
            if (edit_message($data)) {
                redirect("index.php?page={$data['page']}#message-{$data['id']}");
            }
        } else {
            $_SESSION['errors'] = get_errors($v->errors());
        }
    }
}

if (isset($_GET['do']) && $_GET['do'] == 'toggle-status') {
    $id = $_GET['id'] ?? 0;
    $status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
    toggle_message_status($status, $id);
    $page = isset($_GET['page']) ? "?page=" . (int)$_GET['page'] : "?page = 1";
    redirect("index.php{$page}#message-{$id}");
}

$page = $_GET['page'] ?? 1;
$per_page = 4;
$total = get_count_messages();
$pagination = new Pagination((int) $page, $per_page, $total);
$start = $pagination->getStart();

$messages = get_messages($start, $per_page);

require_once __DIR__ . '/views/index.tpl.php';
