<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/incs/db.php';
require_once __DIR__ . '/incs/functions.php';

$title = 'Home';

/** @var PDO $db */

if (isset($_POST['send-message'])) {
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

$messages = get_messages();
//dump($messages);

require_once __DIR__ . '/views/index.tpl.php';
