<?php

namespace Core;

/**
 * HTTP Request class for handling incoming requests
 */
class Request
{
  private string $method;
  private string $uri;
  private array $headers;
  private array $query;
  private array $post;
  private array $server;
  private array $cookies;
  private array $files;
  private ?string $body;
  private array $routeParams = [];

  public function __construct()
  {
    $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $this->uri = $this->parseUri();
    $this->headers = $this->parseHeaders();
    $this->query = $_GET ?? [];
    $this->post = $_POST ?? [];
    $this->server = $_SERVER ?? [];
    $this->cookies = $_COOKIE ?? [];
    $this->files = $_FILES ?? [];
    $this->body = $this->parseBody();
  }

  /**
   * Parse the request URI
   */
  private function parseUri(): string
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Remove query string
    if (($pos = strpos($uri, '?')) !== false) {
      $uri = substr($uri, 0, $pos);
    }

    // Remove .php extension for modern routing
    $uri = str_replace('.php', '', $uri);

    // Normalize the path
    $uri = '/' . trim($uri, '/');

    return $uri;
  }

  /**
   * Parse request headers
   */
  private function parseHeaders(): array
  {
    $headers = [];

    if (function_exists('getallheaders')) {
      $headers = getallheaders();
    } else {
      foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
    }

    return $headers ?? [];
  }

  /**
   * Parse request body
   */
  private function parseBody(): ?string
  {
    if ($this->method === 'POST' || $this->method === 'PUT' || $this->method === 'PATCH') {
      $contentType = $this->getHeader('Content-Type') ?? '';

      if (strpos($contentType, 'application/json') !== false) {
        return file_get_contents('php://input');
      }
    }

    return null;
  }

  /**
   * Get HTTP method
   */
  public function getMethod(): string
  {
    return strtoupper($this->method);
  }

  /**
   * Get request URI
   */
  public function getUri(): string
  {
    return $this->uri;
  }

  /**
   * Get specific header
   */
  public function getHeader(string $name): ?string
  {
    $name = str_replace('_', '-', strtolower($name));

    foreach ($this->headers as $key => $value) {
      if (strtolower($key) === $name) {
        return $value;
      }
    }

    return null;
  }

  /**
   * Get all headers
   */
  public function getHeaders(): array
  {
    return $this->headers;
  }

  /**
   * Get query parameters
   */
  public function getQuery(): array
  {
    return $this->query;
  }

  /**
   * Get specific query parameter
   */
  public function getQueryParam(string $name, $default = null)
  {
    return $this->query[$name] ?? $default;
  }

  /**
   * Get POST data
   */
  public function getPost(): array
  {
    return $this->post;
  }

  /**
   * Get specific POST parameter
   */
  public function getPostParam(string $name, $default = null)
  {
    return $this->post[$name] ?? $default;
  }

  /**
   * Get JSON body as array
   */
  public function getJsonBody(): array
  {
    if ($this->body === null) {
      return [];
    }

    $data = json_decode($this->body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Invalid JSON in request body');
    }

    return $data ?? [];
  }

  /**
   * Get raw body
   */
  public function getBody(): ?string
  {
    return $this->body;
  }

  /**
   * Get server variables
   */
  public function getServer(): array
  {
    return $this->server;
  }

  /**
   * Get cookies
   */
  public function getCookies(): array
  {
    return $this->cookies;
  }

  /**
   * Get uploaded files
   */
  public function getFiles(): array
  {
    return $this->files;
  }

  /**
   * Set route parameters
   */
  public function setRouteParams(array $params): void
  {
    $this->routeParams = $params;
  }

  /**
   * Get route parameters
   */
  public function getRouteParams(): array
  {
    return $this->routeParams;
  }

  /**
   * Get specific route parameter
   */
  public function getRouteParam(string $name, $default = null)
  {
    return $this->routeParams[$name] ?? $default;
  }

  /**
   * Check if request is AJAX
   */
  public function isAjax(): bool
  {
    return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
  }

  /**
   * Check if request wants JSON
   */
  public function wantsJson(): bool
  {
    $accept = $this->getHeader('Accept') ?? '';
    return strpos($accept, 'application/json') !== false;
  }

  /**
   * Check if request is secure (HTTPS)
   */
  public function isSecure(): bool
  {
    return (
      (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
      (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
  }

  /**
   * Get client IP address
   */
  public function getClientIp(): string
  {
    return $this->server['HTTP_X_FORWARDED_FOR'] ??
      $this->server['HTTP_CLIENT_IP'] ??
      $this->server['REMOTE_ADDR'] ??
      'unknown';
  }

  /**
   * Get user agent
   */
  public function getUserAgent(): ?string
  {
    return $this->getHeader('User-Agent');
  }
}
