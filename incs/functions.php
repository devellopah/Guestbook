<?php

function dump(array|object $data): void
{
    echo "<pre>" . print_r($data, 1) . "</pre>";
}

function load(array $fillable, $post = true): array
{
    $load_data = $post ? $_POST : $_GET;
    $data = [];
    foreach ($fillable as $field) {
        if (isset($load_data[$field])) {
            $data[$field] = trim($load_data[$field]);
        } else {
            $data[$field] = '';
        }
    }
    return $data;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES);
}

function old(string $name, $post = true): string
{
    $load_data = $post ? $_POST : $_GET;
    return isset($load_data[$name]) ? h($load_data[$name]) : '';
}

function redirect(string $url = ''): never
{
    header("Location: {$url}");
    die;
}

function get_errors(array $errors): string
{
    $html = '<ul class="list-unstyled">';
    foreach ($errors as $error_group) {
        foreach ($error_group as $error) {
            $html .= "<li>{$error}</li>";
        }
    }
    $html .= '</ul>';
    return $html;
}

function register(array $data): bool
{
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetchColumn()) {
        $_SESSION['errors'] = 'This email is already taken';
        return false;
    }

    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->execute($data);
    $_SESSION['success'] = 'You have successfully registered';
    return true;
}
