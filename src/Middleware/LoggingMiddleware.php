<?php

namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Services\LoggingService;

/**
 * Logging middleware for request/response logging
 */
class LoggingMiddleware extends Middleware
{
  private LoggingService $logger;

  public function __construct()
  {
    $this->logger = new LoggingService();
  }

  /**
   * Handle the incoming request
   */
  public function handle(Request $request, callable $next): Response
  {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    // Log incoming request
    $this->logRequest($request);

    // Continue to next middleware/handler
    $response = $next($request);

    // Calculate execution time and memory usage
    $executionTime = microtime(true) - $startTime;
    $memoryUsage = memory_get_usage() - $startMemory;

    // Log response
    $this->logResponse($request, $response, $executionTime, $memoryUsage);

    return $response;
  }

  /**
   * Log incoming request
   */
  private function logRequest(Request $request): void
  {
    $logData = [
      'method' => $request->getMethod(),
      'uri' => $request->getUri(),
      'ip' => $request->getClientIp(),
      'user_agent' => $request->getUserAgent(),
      'query_params' => $request->getQuery(),
      'headers' => $this->sanitizeHeaders($request->getHeaders()),
      'timestamp' => date('c')
    ];

    // Don't log sensitive data in production
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
      $logData['post_data'] = $this->sanitizePostData($request->getPost());
      if ($request->getBody()) {
        $logData['json_body'] = $this->sanitizeJsonBody($request->getJsonBody());
      }
    }

    $this->logger->info('Incoming Request', $logData);
  }

  /**
   * Log response
   */
  private function logResponse(Request $request, Response $response, float $executionTime, int $memoryUsage): void
  {
    $logData = [
      'method' => $request->getMethod(),
      'uri' => $request->getUri(),
      'status_code' => $response->getStatusCode(),
      'execution_time' => round($executionTime * 1000, 2) . 'ms',
      'memory_usage' => $this->formatBytes($memoryUsage),
      'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
      'timestamp' => date('c')
    ];

    // Log as warning for 4xx/5xx status codes
    if ($response->getStatusCode() >= 400) {
      $this->logger->warning('Response with error status', $logData);
    } else {
      $this->logger->info('Response sent', $logData);
    }
  }

  /**
   * Sanitize headers (remove sensitive data)
   */
  private function sanitizeHeaders(array $headers): array
  {
    $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
    $sanitized = [];

    foreach ($headers as $name => $value) {
      if (in_array(strtolower($name), $sensitiveHeaders)) {
        $sanitized[$name] = '[REDACTED]';
      } else {
        $sanitized[$name] = $value;
      }
    }

    return $sanitized;
  }

  /**
   * Sanitize POST data (remove passwords, tokens, etc.)
   */
  private function sanitizePostData(array $data): array
  {
    $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
    $sanitized = [];

    foreach ($data as $key => $value) {
      if (in_array(strtolower($key), $sensitiveFields)) {
        $sanitized[$key] = '[REDACTED]';
      } else {
        $sanitized[$key] = $value;
      }
    }

    return $sanitized;
  }

  /**
   * Sanitize JSON body (remove sensitive data)
   */
  private function sanitizeJsonBody(array $data): array
  {
    return $this->sanitizePostData($data);
  }

  /**
   * Format bytes to human readable format
   */
  private function formatBytes(int $bytes): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
  }
}
