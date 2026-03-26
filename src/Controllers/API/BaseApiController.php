<?php

namespace Controllers\API;

use Core\BaseController;
use Services\LoggingService;
use Exception;

/**
 * Base API Controller with common functionality for all API endpoints
 */
class BaseApiController extends BaseController
{
  protected LoggingService $logger;

  public function __construct()
  {
    // Initialize logging service
    $this->logger = new LoggingService();
  }

  /**
   * Send JSON response with proper headers
   */
  protected function jsonResponse(array $data, int $statusCode = 200): void
  {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Handle OPTIONS preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      exit;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
  }

  /**
   * Send success response
   */
  protected function successResponse(array $data = [], string $message = 'Success', int $statusCode = 200): void
  {
    $response = [
      'success' => true,
      'data' => $data,
      'message' => $message,
      'meta' => [
        'timestamp' => date('c'),
        'version' => 'v1'
      ]
    ];

    $this->jsonResponse($response, $statusCode);
  }

  /**
   * Send error response
   */
  protected function errorResponse(string $message = 'An error occurred', int $statusCode = 400, array $details = []): void
  {
    $response = [
      'success' => false,
      'error' => [
        'message' => $message,
        'code' => $statusCode,
        'details' => $details
      ],
      'meta' => [
        'timestamp' => date('c'),
        'version' => 'v1'
      ]
    ];

    $this->jsonResponse($response, $statusCode);
  }

  /**
   * Get request data from JSON body
   */
  protected function getJsonInput(): array
  {
    $input = file_get_contents('php://input');

    if (empty($input)) {
      return [];
    }

    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON input');
    }

    return $data ?? [];
  }

  /**
   * Validate required fields
   */
  protected function validateRequired(array $data, array $requiredFields): void
  {
    foreach ($requiredFields as $field) {
      if (!isset($data[$field]) || $data[$field] === '') {
        throw new Exception("Field '{$field}' is required");
      }
    }
  }

  /**
   * Get pagination parameters
   */
  protected function getPaginationParams(): array
  {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 10)));

    return [
      'page' => $page,
      'per_page' => $perPage,
      'offset' => ($page - 1) * $perPage
    ];
  }

  /**
   * Check if request is authenticated
   */
  protected function checkAuth(): bool
  {
    return isset($_SESSION['user_id']);
  }

  /**
   * Get current user
   */
  protected function getUser(): ?array
  {
    if (!$this->checkAuth()) {
      return null;
    }

    return [
      'id' => $_SESSION['user_id'],
      'email' => $_SESSION['user_email'],
      'role' => $_SESSION['user_role']
    ];
  }

  /**
   * Check if user is admin
   */
  protected function checkAdmin(): bool
  {
    if (!$this->checkAuth()) {
      return false;
    }

    $user = $this->getUser();
    return $user && $user['role'] >= 2; // ADMIN role
  }

  /**
   * Log API request
   */
  protected function logRequest(string $endpoint, string $method, array $data = []): void
  {
    $this->logger->info('API Request', [
      'endpoint' => $endpoint,
      'method' => $method,
      'user_id' => $this->getUser()['id'] ?? null,
      'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
      'data' => $data
    ]);
  }
}
