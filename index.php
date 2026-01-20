<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/incs/db.php';
require_once __DIR__ . '/incs/functions.php';
require_once __DIR__ . '/incs/Pagination.php';

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

$page = $_GET['page'] ?? 1;
$per_page = 2;
$total = get_count_messages();
$pagination = new Pagination((int) $page, $per_page, $total);
$start = $pagination->getStart();

$messages = get_messages($start, $per_page);
//dump($messages);

require_once __DIR__ . '/views/index.tpl.php';
