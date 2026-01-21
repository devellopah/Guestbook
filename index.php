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

if (isset($_POST['edit-message'])) {
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
