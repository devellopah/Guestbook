<?php

namespace Core;

/**
 * HTTP Response class for handling outgoing responses
 */
class Response
{
  private int $statusCode = 200;
  private array $headers = [];
  private ?string $body = null;
  private array $data = [];
  private bool $json = false;

  /**
   * Set response status code
   */
  public function setStatusCode(int $code): self
  {
    $this->statusCode = $code;
    return $this;
  }

  /**
   * Get response status code
   */
  public function getStatusCode(): int
  {
    return $this->statusCode;
  }

  /**
   * Set response header
   */
  public function setHeader(string $name, string $value): self
  {
    $this->headers[$name] = $value;
    return $this;
  }

  /**
   * Get response headers
   */
  public function getHeaders(): array
  {
    return $this->headers;
  }

  /**
   * Set response body
   */
  public function setBody(string $body): self
  {
    $this->body = $body;
    return $this;
  }

  /**
   * Get response body
   */
  public function getBody(): ?string
  {
    return $this->body;
  }

  /**
   * Set JSON response data
   */
  public function setJson(array $data): self
  {
    $this->data = $data;
    $this->json = true;
    $this->setHeader('Content-Type', 'application/json');
    return $this;
  }

  /**
   * Check if response is JSON
   */
  public function isJson(): bool
  {
    return $this->json;
  }

  /**
   * Get JSON data
   */
  public function getJsonData(): array
  {
    return $this->data;
  }

  /**
   * Send JSON response
   */
  public function json(array $data, int $statusCode = 200): void
  {
    $this->setJson($data);
    $this->setStatusCode($statusCode);
    $this->send();
  }

  /**
   * Send success JSON response
   */
  public function success(array $data = [], string $message = 'Success', int $statusCode = 200): void
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

    $this->json($response, $statusCode);
  }

  /**
   * Send error JSON response
   */
  public function error(string $message = 'An error occurred', int $statusCode = 400, array $details = []): void
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

    $this->json($response, $statusCode);
  }

  /**
   * Redirect to URL
   */
  public function redirect(string $url, int $statusCode = 302): void
  {
    $this->setStatusCode($statusCode);
    $this->setHeader('Location', $url);
    $this->send();
  }

  /**
   * Send HTML response
   */
  public function html(string $html, int $statusCode = 200): void
  {
    $this->setBody($html);
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
    $this->send();
  }

  /**
   * Send plain text response
   */
  public function text(string $text, int $statusCode = 200): void
  {
    $this->setBody($text);
    $this->setStatusCode($statusCode);
    $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
    $this->send();
  }

  /**
   * Send the response
   */
  public function send(): void
  {
    // Set HTTP status code
    http_response_code($this->statusCode);

    // Set headers
    foreach ($this->headers as $name => $value) {
      header("$name: $value");
    }

    // Set CORS headers if not already set
    if (!isset($this->headers['Access-Control-Allow-Origin'])) {
      header('Access-Control-Allow-Origin: *');
    }
    if (!isset($this->headers['Access-Control-Allow-Methods'])) {
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
    if (!isset($this->headers['Access-Control-Allow-Headers'])) {
      header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }

    // Handle OPTIONS preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      exit;
    }

    // Send body
    if ($this->json) {
      echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } elseif ($this->body !== null) {
      echo $this->body;
    }

    exit;
  }

  /**
   * Create response from exception
   */
  public static function fromException(\Exception $e, int $statusCode = 500): self
  {
    $response = new self();
    $response->error($e->getMessage(), $statusCode);
    return $response;
  }

  /**
   * Create a new response instance
   */
  public static function create(): self
  {
    return new self();
  }
}
