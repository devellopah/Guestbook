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

    try {
        // Check if email already exists
        $stmt = db_query("SELECT COUNT(*) FROM users WHERE email = ?", [$data['email']]);
        if ($stmt->fetchColumn()) {
            $_SESSION['errors'] = 'This email is already taken';
            return false;
        }

        // Hash password and insert user
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = db_query("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)", $data);

        $_SESSION['success'] = 'You have successfully registered';
        return true;
    } catch (DatabaseException $e) {
        log_error("Registration error: " . $e->getMessage(), ['email' => $data['email']]);
        $_SESSION['errors'] = 'Unable to register. Please try again later.';
        return false;
    } catch (Exception $e) {
        log_error("Registration failed: " . $e->getMessage(), $data);
        $_SESSION['errors'] = 'An error occurred during registration.';
        return false;
    }
}

function login(array $data): bool
{
    global $db;

    try {
        // Check user credentials
        $stmt = db_query("SELECT * FROM users WHERE email = ?", [$data['email']]);
        if ($row = $stmt->fetch()) {
            if (!password_verify($data['password'], $row['password'])) {
                $_SESSION['errors'] = 'Wrong email or password';
                return false;
            }
        } else {
            $_SESSION['errors'] = 'Wrong email or password';
            return false;
        }

        // Set session data
        foreach ($row as $key => $value) {
            if ($key != 'password') {
                $_SESSION['user'][$key] = $value;
            }
        }
        $_SESSION['success'] = 'Successfully login';
        return true;
    } catch (DatabaseException $e) {
        log_error("Login error: " . $e->getMessage(), ['email' => $data['email']]);
        $_SESSION['errors'] = 'Unable to login. Please try again later.';
        return false;
    } catch (Exception $e) {
        log_error("Login failed: " . $e->getMessage(), ['email' => $data['email']]);
        $_SESSION['errors'] = 'An error occurred during login.';
        return false;
    }
}

function save_message(array $data): bool
{
    global $db;
    if (!check_auth()) {
        $_SESSION['errors'] = 'Login is required';
        return false;
    }

    try {
        $stmt = db_query("INSERT INTO messages (user_id, message) VALUES (?, ?)", [
            $_SESSION['user']['id'],
            $data['message']
        ]);
        $_SESSION['success'] = 'Message added';
        return true;
    } catch (DatabaseException $e) {
        $_SESSION['errors'] = 'Unable to save message. Please try again later.';
        return false;
    } catch (Exception $e) {
        log_error("Message save error: " . $e->getMessage(), $data);
        $_SESSION['errors'] = 'An error occurred while saving your message.';
        return false;
    }
}

function edit_message(array $data): bool
{
    global $db;
    if (!check_admin()) {
        $_SESSION['errors'] = 'Forbidden';
        return false;
    }

    try {
        $stmt = db_query("UPDATE messages SET message = ? WHERE id = ?", [
            $data['message'],
            $data['id']
        ]);
        $_SESSION['success'] = 'Message was saved';
        return true;
    } catch (DatabaseException $e) {
        $_SESSION['errors'] = 'Unable to save message. Please try again later.';
        return false;
    } catch (Exception $e) {
        log_error("Message edit error: " . $e->getMessage(), $data);
        $_SESSION['errors'] = 'An error occurred while saving your message.';
        return false;
    }
}

function get_messages(int $start, int $per_page): array
{
    global $db;
    $where = '';
    if (!check_admin()) {
        $where = 'WHERE status = 1';
    }

    try {
        $sql = "SELECT m.id, m.user_id, m.message, m.status, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS created_at, users.name 
                FROM messages m JOIN users ON users.id = m.user_id {$where} 
                ORDER BY id DESC LIMIT :start, :per_page";

        $stmt = db_query($sql, [
            'start' => $start,
            'per_page' => $per_page
        ]);

        return $stmt->fetchAll();
    } catch (DatabaseException $e) {
        log_error("Get messages error: " . $e->getMessage(), [
            'start' => $start,
            'per_page' => $per_page
        ]);
        return [];
    } catch (Exception $e) {
        log_error("Messages fetch error: " . $e->getMessage());
        return [];
    }
}

function toggle_message_status(int $status, int $id): bool
{
    global $db;
    if (!check_admin()) {
        $_SESSION['errors'] = 'Forbidden';
        return false;
    }

    try {
        $status = $status ? 1 : 0;
        $stmt = db_query("UPDATE messages SET status = ? WHERE id = ?", [
            $status,
            $id
        ]);
        return $stmt->rowCount() > 0;
    } catch (DatabaseException $e) {
        log_error("Toggle status error: " . $e->getMessage(), [
            'status' => $status,
            'id' => $id
        ]);
        $_SESSION['errors'] = 'Unable to update message status. Please try again later.';
        return false;
    } catch (Exception $e) {
        log_error("Status toggle error: " . $e->getMessage());
        $_SESSION['errors'] = 'An error occurred while updating message status.';
        return false;
    }
}

function get_count_messages(): int
{
    global $db;
    $where = '';
    if (!check_admin()) {
        $where = 'WHERE status = 1';
    }

    try {
        $sql = "SELECT COUNT(*) FROM messages {$where}";
        $stmt = db_query($sql);
        return $stmt->fetchColumn();
    } catch (DatabaseException $e) {
        log_error("Count messages error: " . $e->getMessage());
        return 0;
    } catch (Exception $e) {
        log_error("Messages count error: " . $e->getMessage());
        return 0;
    }
}

function check_auth(): bool
{
    return isset($_SESSION['user']);
}

function check_admin(): bool
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == 2;
}

function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_token_field(): string
{
    $token = generate_csrf_token();
    return '<!-- CSRF Token: ' . $token . ' -->' . "\n" .
        '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function check_rate_limit(string $action, int $max_attempts = 5, int $time_window = 300): bool
{
    $key = "rate_limit_{$action}_" . session_id();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }

    $current_time = time();
    $session = &$_SESSION[$key];

    // Reset if time window has passed
    if ($current_time - $session['first_attempt'] > $time_window) {
        $session = [
            'count' => 0,
            'first_attempt' => $current_time
        ];
    }

    // Check if limit exceeded
    if ($session['count'] >= $max_attempts) {
        return false;
    }

    // Increment counter
    $session['count']++;
    return true;
}

function get_rate_limit_remaining_time(string $action): int
{
    $key = "rate_limit_{$action}_" . session_id();

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $session = $_SESSION[$key];
    $time_window = 300; // 5 minutes
    $elapsed = time() - $session['first_attempt'];

    return max(0, $time_window - $elapsed);
}

// Custom Exception Classes
class ValidationException extends Exception {}
class DatabaseException extends Exception {}
class SecurityException extends Exception {}

// Enhanced Error Logging
function log_error(string $message, array $context = []): void
{
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'session_id' => session_id()
    ];
    error_log(json_encode($log_entry));
}


// Enhanced Input Validation
function validate_input(string $input, string $field_name, int $max_length = 1000): string
{
    try {
        $input = trim($input);

        if (empty($input)) {
            throw new ValidationException("{$field_name} cannot be empty");
        }

        if (strlen($input) > $max_length) {
            throw new ValidationException("{$field_name} is too long (max {$max_length} characters)");
        }

        return $input;
    } catch (ValidationException $e) {
        throw $e;
    } catch (Exception $e) {
        throw new ValidationException("Invalid input for {$field_name}");
    }
}

// Enhanced Database Query Function
function db_query(string $sql, array $params = []): PDOStatement
{
    global $db;
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        log_error("Database error: " . $e->getMessage(), [
            'sql' => $sql,
            'params' => $params
        ]);
        throw new DatabaseException("Database service temporarily unavailable");
    }
}

// Enhanced CSRF Validation with Exception Handling
function validate_csrf_token_safe(string $token): bool
{
    try {
        if (!validate_csrf_token($token)) {
            throw new SecurityException("Invalid CSRF token");
        }
        return true;
    } catch (SecurityException $e) {
        log_error("Security violation: " . $e->getMessage(), [
            'csrf_token' => substr($token, 0, 8) . '...',
            'session_id' => session_id()
        ]);
        throw $e;
    } catch (Exception $e) {
        log_error("CSRF validation error: " . $e->getMessage());
        throw new SecurityException("Security validation failed");
    }
}

// Safe Session Data Access
function safe_session_get(string $key, $default = null)
{
    try {
        return $_SESSION[$key] ?? $default;
    } catch (Exception $e) {
        log_error("Session access error: " . $e->getMessage(), ['key' => $key]);
        return $default;
    }
}

// Enhanced Rate Limiting with Exception Handling
function check_rate_limit_safe(string $action, int $max_attempts = 5, int $time_window = 300): bool
{
    try {
        return check_rate_limit($action, $max_attempts, $time_window);
    } catch (Exception $e) {
        log_error("Rate limit check error: " . $e->getMessage(), ['action' => $action]);
        // Fail open for rate limiting to not block legitimate users
        return true;
    }
}
