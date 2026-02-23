<?php

namespace Core;

use Controllers\UserController;

abstract class BaseController
{
  protected function render(string $view, array $data = []): void
  {
    // Extract data to variables
    extract($data);

    // Set default title
    $title = $title ?? 'Guestbook';

    // Pass user data from session to view variables (only if not already set)
    $user = $user ?? $this->getUser();

    // Pass flash messages from session to view variables (only if not already set)
    $flash = $flash ?? $this->getFlash();

    // Include the base layout
    require_once __DIR__ . '/../Views/layouts/base.php';
  }

  protected function redirect(string $url): void
  {
    header("Location: {$url}");
    exit;
  }

  protected function isPost(): bool
  {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
  }

  protected function getPostData(array $fields): array
  {
    $data = [];
    foreach ($fields as $field) {
      $data[$field] = trim($_POST[$field] ?? '');
    }
    return $data;
  }

  protected function getQueryData(array $fields): array
  {
    $data = [];
    foreach ($fields as $field) {
      $data[$field] = trim($_GET[$field] ?? '');
    }
    return $data;
  }

  protected function validateCsrfToken(): bool
  {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    // Debug logging
    error_log("CSRF Debug - Submitted token: " . substr($submittedToken, 0, 10) . "...");
    error_log("CSRF Debug - Session token: " . substr($sessionToken, 0, 10) . "...");
    error_log("CSRF Debug - Tokens equal: " . ($submittedToken === $sessionToken ? 'YES' : 'NO'));
    error_log("CSRF Debug - Session token exists: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO'));
    error_log("CSRF Debug - POST token exists: " . (isset($_POST['csrf_token']) ? 'YES' : 'NO'));

    return isset($_POST['csrf_token']) && hash_equals(
      $_SESSION['csrf_token'] ?? '',
      $_POST['csrf_token']
    );
  }

  protected function generateCsrfToken(): string
  {
    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }

  protected function getCsrfToken(): string
  {
    return $this->generateCsrfToken();
  }

  protected function csrfTokenField(): string
  {
    // Only generate token if it doesn't exist to prevent regeneration on page refresh
    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $token = $_SESSION['csrf_token'];

    return '<!-- CSRF Token: ' . $token . ' -->' . "\n" .
      '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
  }

  protected function checkAuth(): bool
  {
    return isset($_SESSION['user']);
  }

  protected function checkAdmin(): bool
  {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] == 2;
  }

  protected function getUser(): ?array
  {
    return $_SESSION['user'] ?? null;
  }

  protected function flash(string $type, string $message): void
  {
    $_SESSION['flash'] = [
      'type' => $type,
      'message' => $message
    ];
  }

  protected function getFlash(): ?array
  {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
  }

  protected function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
  {
    $key = "rate_limit_{$action}_" . session_id();

    if (!isset($_SESSION[$key])) {
      $_SESSION[$key] = [
        'count' => 0,
        'first_attempt' => time()
      ];
    }

    $currentTime = time();
    $session = &$_SESSION[$key];

    // Reset if time window has passed
    if ($currentTime - $session['first_attempt'] > $timeWindow) {
      $session = [
        'count' => 0,
        'first_attempt' => $currentTime
      ];
    }

    // Check if limit exceeded
    if ($session['count'] >= $maxAttempts) {
      return false;
    }

    // Increment counter
    $session['count']++;
    return true;
  }

  protected function getRateLimitRemainingTime(string $action): int
  {
    $key = "rate_limit_{$action}_" . session_id();

    if (!isset($_SESSION[$key])) {
      return 0;
    }

    $session = $_SESSION[$key];
    $timeWindow = 300; // 5 minutes
    $elapsed = time() - $session['first_attempt'];

    return max(0, $timeWindow - $elapsed);
  }

  protected function validateInput(string $input, string $fieldName, int $maxLength = 1000): string
  {
    $input = trim($input);

    if (empty($input)) {
      throw new \Exception("{$fieldName} cannot be empty");
    }

    if (strlen($input) > $maxLength) {
      throw new \Exception("{$fieldName} is too long (max {$maxLength} characters)");
    }

    return $input;
  }

  protected function h(string $s): string
  {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }

  protected function old(string $name, $post = true): string
  {
    $loadData = $post ? $_POST : $_GET;
    return isset($loadData[$name]) ? $this->h(trim($loadData[$name])) : '';
  }
}
