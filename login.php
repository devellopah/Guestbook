<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/incs/db.php';
require_once __DIR__ . '/incs/functions.php';

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
