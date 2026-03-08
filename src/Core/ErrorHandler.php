<?php

namespace Core;

class ErrorHandler
{
  private static bool $initialized = false;

  public static function init(): void
  {
    if (self::$initialized) {
      return;
    }

    // Set custom error and exception handlers
    set_error_handler([self::class, 'handleError']);
    set_exception_handler([self::class, 'handleException']);
    register_shutdown_function([self::class, 'handleShutdown']);

    self::$initialized = true;
  }

  public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
  {
    // Convert PHP errors to exceptions
    if (!(error_reporting() & $errno)) {
      // This error code is not included in error_reporting
      return false;
    }

    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
  }

  public static function handleException(\Throwable $exception): void
  {
    // Log the exception
    self::logException($exception);

    // Determine if we should show detailed error info
    $isDebug = self::isDebugMode();

    // Set HTTP status code
    $statusCode = self::getStatusCode($exception);

    // Clear any output that might have been started
    if (ob_get_level()) {
      ob_clean();
    }

    // Set response headers
    http_response_code($statusCode);

    // Render error page
    self::renderErrorPage($exception, $statusCode, $isDebug);
  }

  public static function handleShutdown(): void
  {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
      self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
    }
  }

  private static function logException(\Throwable $exception): void
  {
    // Log to system error log (simpler approach)
    $timestamp = date('Y-m-d H:i:s');
    $message = sprintf(
      "[%s] %s: %s in %s:%d",
      $timestamp,
      get_class($exception),
      $exception->getMessage(),
      $exception->getFile(),
      $exception->getLine()
    );

    error_log($message);
  }

  private static function isDebugMode(): bool
  {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
  }

  private static function getStatusCode(\Throwable $exception): int
  {
    // Check for specific exception types first
    if ($exception instanceof \InvalidArgumentException) {
      return 400; // Bad Request
    }

    if ($exception instanceof \OutOfBoundsException) {
      return 404; // Not Found
    }

    if ($exception instanceof \RuntimeException) {
      return 500; // Internal Server Error
    }

    // Default to 500 for other exceptions
    return 500;
  }

  private static function renderErrorPage(\Throwable $exception, int $statusCode, bool $isDebug): void
  {
    // Prepare error data
    $errorData = [
      'statusCode' => $statusCode,
      'message' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'trace' => $exception->getTraceAsString(),
      'isDebug' => $isDebug
    ];

    // Try to render error page
    try {
      if (file_exists(__DIR__ . '/../Views/errors/error.php')) {
        extract($errorData);
        require_once __DIR__ . '/../Views/errors/error.php';
      } else {
        // Fallback to simple error page
        self::renderSimpleErrorPage($errorData);
      }
    } catch (\Throwable $renderException) {
      // If error page rendering fails, show basic error
      self::renderSimpleErrorPage($errorData);
    }
  }

  private static function renderSimpleErrorPage(array $errorData): void
  {
    $statusCode = $errorData['statusCode'];
    $message = htmlspecialchars($errorData['message'], ENT_QUOTES, 'UTF-8');

    if ($errorData['isDebug']) {
      $file = htmlspecialchars($errorData['file'], ENT_QUOTES, 'UTF-8');
      $line = $errorData['line'];
      $trace = nl2br(htmlspecialchars($errorData['trace'], ENT_QUOTES, 'UTF-8'));

      echo "<!DOCTYPE html>
<html>
<head>
    <title>Error {$statusCode}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .error { border: 1px solid #ddd; padding: 20px; background: #f9f9f9; }
        .trace { margin-top: 20px; background: #fff; border: 1px solid #ddd; padding: 15px; }
        .file { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Error {$statusCode}</h1>
    <div class='error'>
        <h2>{$message}</h2>
        <p class='file'>File: {$file} (line {$line})</p>
    </div>
    <div class='trace'>
        <h3>Stack Trace:</h3>
        <pre>{$trace}</pre>
    </div>
</body>
</html>";
    } else {
      echo "<!DOCTYPE html>
<html>
<head>
    <title>Error {$statusCode}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        h1 { color: #d32f2f; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>Oops! Something went wrong</h1>
    <p>We're sorry, but an error occurred while processing your request.</p>
    <p>Please try again later or contact support if the problem persists.</p>
    <p><a href='/'>Return to Home</a></p>
</body>
</html>";
    }
  }
}
