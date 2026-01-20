<?php

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/incs/db.php';
require_once __DIR__ . '/incs/functions.php';

if (check_auth()) {
    redirect('index.php');
}

$title = 'Register';

/** @var PDO $db */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = load(['name', 'email', 'password']);

    $v = new \Valitron\Validator($data);
    $v->rules([
        'required' => ['name', 'email', 'password'],
        'email' => ['email'],
        'lengthMin' => [
            ['password', 6]
        ],
        'lengthMax' => [
            ['name', 50],
            ['email', 50],
        ]
    ]);

    if ($v->validate()) {
        if (register($data)) {
            redirect('login.php');
        }
    } else {
        $_SESSION['errors'] = get_errors($v->errors());
    }
}

require_once __DIR__ . '/views/register.tpl.php';
