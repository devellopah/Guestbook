<?php

namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * CORS middleware for handling Cross-Origin Resource Sharing
 */
class CORSMiddleware extends Middleware
{
  private array $allowedOrigins;
  private array $allowedMethods;
  private array $allowedHeaders;
  private bool $allowCredentials;
  private int $maxAge;

  public function __construct(
    array $allowedOrigins = ['*'],
    array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-API-Key'],
    bool $allowCredentials = false,
    int $maxAge = 86400 // 24 hours
  ) {
    $this->allowedOrigins = $allowedOrigins;
    $this->allowedMethods = $allowedMethods;
    $this->allowedHeaders = $allowedHeaders;
    $this->allowCredentials = $allowCredentials;
    $this->maxAge = $maxAge;
  }

  /**
   * Handle the incoming request
   */
  public function handle(Request $request, callable $next): Response
  {
    // Handle preflight OPTIONS request
    if ($request->getMethod() === 'OPTIONS') {
      return $this->handlePreflight($request);
    }

    // Continue to next middleware/handler
    $response = $next($request);

    // Add CORS headers to response
    $this->addCorsHeaders($request, $response);

    return $response;
  }

  /**
   * Handle preflight OPTIONS request
   */
  private function handlePreflight(Request $request): Response
  {
    $response = Response::create();

    // Check if origin is allowed
    $origin = $request->getHeader('Origin');
    if ($origin && !$this->isOriginAllowed($origin)) {
      $response->setStatusCode(403);
      $response->setBody('Origin not allowed');
      return $response;
    }

    // Check if method is allowed
    $method = $request->getHeader('Access-Control-Request-Method');
    if ($method && !in_array($method, $this->allowedMethods)) {
      $response->setStatusCode(403);
      $response->setBody('Method not allowed');
      return $response;
    }

    // Check if headers are allowed
    $headers = $request->getHeader('Access-Control-Request-Headers');
    if ($headers) {
      $requestedHeaders = array_map('trim', explode(',', $headers));
      foreach ($requestedHeaders as $header) {
        if (!in_array($header, $this->allowedHeaders)) {
          $response->setStatusCode(403);
          $response->setBody('Header not allowed: ' . $header);
          return $response;
        }
      }
    }

    // Set preflight response headers
    $this->setCorsHeaders($request, $response);
    $response->setStatusCode(204); // No Content
    $response->setBody('');

    return $response;
  }

  /**
   * Add CORS headers to response
   */
  private function addCorsHeaders(Request $request, Response $response): void
  {
    $origin = $request->getHeader('Origin');

    if ($origin && $this->isOriginAllowed($origin)) {
      $response->setHeader('Access-Control-Allow-Origin', $origin);
    } elseif (in_array('*', $this->allowedOrigins)) {
      $response->setHeader('Access-Control-Allow-Origin', '*');
    }

    $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
    $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
    $response->setHeader('Access-Control-Max-Age', (string) $this->maxAge);

    if ($this->allowCredentials) {
      $response->setHeader('Access-Control-Allow-Credentials', 'true');
    }

    // Expose headers that JavaScript can access
    $response->setHeader('Access-Control-Expose-Headers', 'X-Request-Id, X-Rate-Limit-Remaining, X-Rate-Limit-Reset');
  }

  /**
   * Set CORS headers for preflight response
   */
  private function setCorsHeaders(Request $request, Response $response): void
  {
    $origin = $request->getHeader('Origin');

    if ($origin && $this->isOriginAllowed($origin)) {
      $response->setHeader('Access-Control-Allow-Origin', $origin);
    } elseif (in_array('*', $this->allowedOrigins)) {
      $response->setHeader('Access-Control-Allow-Origin', '*');
    }

    $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
    $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
    $response->setHeader('Access-Control-Max-Age', (string) $this->maxAge);

    if ($this->allowCredentials) {
      $response->setHeader('Access-Control-Allow-Credentials', 'true');
    }
  }

  /**
   * Check if origin is allowed
   */
  private function isOriginAllowed(string $origin): bool
  {
    if (in_array('*', $this->allowedOrigins)) {
      return true;
    }

    // Parse origin URL
    $parsedOrigin = parse_url($origin);
    if (!$parsedOrigin || !isset($parsedOrigin['host'])) {
      return false;
    }

    $originHost = $parsedOrigin['host'];
    $originScheme = $parsedOrigin['scheme'] ?? 'https';

    foreach ($this->allowedOrigins as $allowedOrigin) {
      // Handle wildcard subdomains (e.g., *.example.com)
      if (strpos($allowedOrigin, '*.') === 0) {
        $domain = substr($allowedOrigin, 2);
        if ($originHost === $domain || substr($originHost, -strlen($domain) - 1) === '.' . $domain) {
          return true;
        }
      } else {
        // Exact match
        $parsedAllowed = parse_url($allowedOrigin);
        if ($parsedAllowed && isset($parsedAllowed['host'])) {
          if (
            $originHost === $parsedAllowed['host'] &&
            ($originScheme === ($parsedAllowed['scheme'] ?? 'https'))
          ) {
            return true;
          }
        }
      }
    }

    return false;
  }

  /**
   * Create CORS middleware with specific configuration
   */
  public static function create(
    array $allowedOrigins = ['*'],
    array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
    bool $allowCredentials = false,
    int $maxAge = 86400
  ): self {
    return new self($allowedOrigins, $allowedMethods, $allowedHeaders, $allowCredentials, $maxAge);
  }

  /**
   * Create CORS middleware for API with credentials
   */
  public static function forApi(array $allowedOrigins = ['*']): self
  {
    return new self(
      $allowedOrigins,
      ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
      ['Content-Type', 'Authorization', 'X-Requested-With', 'X-API-Key'],
      true,
      86400
    );
  }

  /**
   * Create CORS middleware for web application
   */
  public static function forWeb(array $allowedOrigins = ['*']): self
  {
    return new self(
      $allowedOrigins,
      ['GET', 'POST'],
      ['Content-Type', 'X-Requested-With'],
      false,
      3600
    );
  }
}
