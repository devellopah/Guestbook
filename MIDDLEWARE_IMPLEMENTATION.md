# Middleware System Implementation

## Overview

This document describes the middleware system implementation for the Guestbook application. The middleware system provides a way to filter HTTP requests entering your application.

## Architecture

### Core Components

1. **Middleware Abstract Class** (`src/Core/Middleware.php`)
   - Base class for all middleware
   - Provides `handle()` method for processing requests
   - Static `run()` method for executing middleware chain

2. **Request Class** (`src/Core/Request.php`)
   - Encapsulates HTTP request data
   - Provides methods for accessing headers, query params, POST data, JSON body
   - Includes utility methods for AJAX detection, HTTPS check, client IP

3. **Response Class** (`src/Core/Response.php`)
   - Encapsulates HTTP response
   - Methods for JSON responses, HTML, plain text, redirects
   - Automatic CORS headers

4. **Router** (`src/Core/Router.php`)
   - Updated to support middleware
   - Global middleware applied to all routes
   - Route-specific middleware
   - Route groups with common prefix

## Implemented Middleware

### 1. AuthMiddleware

**File:** `src/Middleware/AuthMiddleware.php`

**Purpose:** Protect routes from unauthorized access

**Features:**
- Checks if user is authenticated via session
- Redirects to login page for web requests
- Returns 401 JSON for API requests
- Static methods for role checking

**Usage:**
```php
// Apply to specific route
$this->get('profile', 'UserController', 'profile', ['auth']);

// Or use in route group
$this->group('admin', function () {
  $this->get('users', 'AdminController', 'users');
  $this->get('messages', 'AdminController', 'messages');
}, ['auth']);
```

**Static Methods:**
- `AuthMiddleware::isAuthenticated()` - Check if user is logged in
- `AuthMiddleware::hasRole('admin')` - Check if user has specific role
- `AuthMiddleware::isAdmin()` - Check if user is admin
- `AuthMiddleware::getUserId()` - Get current user ID
- `AuthMiddleware::getUserEmail()` - Get current user email
- `AuthMiddleware::getUserRole()` - Get current user role

### 2. LoggingMiddleware

**File:** `src/Middleware/LoggingMiddleware.php`

**Purpose:** Log all incoming requests and responses

**Features:**
- Logs request method, URI, IP, user agent
- Logs query parameters and headers
- Sanitizes sensitive data (passwords, tokens)
- Logs response status code
- Calculates execution time and memory usage
- Different log levels for errors (4xx/5xx)

**Usage:**
```php
// Applied globally by default
$this->globalMiddleware = [
  LoggingMiddleware::class,
  CORSMiddleware::class,
];
```

**Log Data:**
- Request method and URI
- Client IP and user agent
- Query parameters
- Sanitized headers
- Response status code
- Execution time (ms)
- Memory usage (KB/MB)
- Memory peak usage

### 3. CORSMiddleware

**File:** `src/Middleware/CORSMiddleware.php`

**Purpose:** Handle Cross-Origin Resource Sharing (CORS)

**Features:**
- Handles preflight OPTIONS requests
- Configurable allowed origins, methods, headers
- Support for wildcard subdomains
- Credentials support
- Max-age for preflight caching

**Usage:**
```php
// Default CORS (allow all)
$this->globalMiddleware = [
  CORSMiddleware::class,
];

// Specific configuration
$cors = CORSMiddleware::create(
  ['https://example.com', '*.example.com'],
  ['GET', 'POST', 'PUT', 'DELETE'],
  ['Content-Type', 'Authorization'],
  true, // Allow credentials
  3600 // Max age
);

// API-specific CORS
$cors = CORSMiddleware::forApi(['https://api.example.com']);

// Web-specific CORS
$cors = CORSMiddleware::forWeb(['https://example.com']);
```

**Configuration Options:**
- `allowedOrigins` - Array of allowed origins (supports wildcards)
- `allowedMethods` - Array of allowed HTTP methods
- `allowedHeaders` - Array of allowed headers
- `allowCredentials` - Allow cookies/authentication
- `maxAge` - Preflight cache duration (seconds)

## Router Integration

### Global Middleware

Applied to all routes automatically:
```php
private function registerDefaultMiddleware(): void
{
  $this->globalMiddleware = [
    LoggingMiddleware::class,
    CORSMiddleware::class,
  ];
}
```

### Route-Specific Middleware

Applied to individual routes:
```php
// Apply auth middleware to protected routes
$this->post('messages', 'API\MessagesController', 'create', ['auth']);
$this->put('messages/{id}', 'API\MessagesController', 'update', ['auth']);
$this->delete('messages/{id}', 'API\MessagesController', 'delete', ['auth']);
```

### Route Groups

Apply middleware to a group of routes:
```php
$this->group('api/v1', function () {
  // Auth routes (no auth required)
  $this->post('auth/login', 'API\AuthController', 'login');
  $this->post('auth/register', 'API\AuthController', 'register');
  
  // Protected routes
  $this->get('messages', 'API\MessagesController', 'index');
  $this->post('messages', 'API\MessagesController', 'create', ['auth']);
}, ['cors']); // Apply CORS to all routes in group
```

## Middleware Execution Order

1. Global middleware executes first (in order defined)
2. Route-specific middleware executes after global
3. Middleware executes in order, then reverses
4. Final handler (controller) executes between middleware

**Example:**
```php
// Global: [LoggingMiddleware, CORSMiddleware]
// Route: [AuthMiddleware]

// Execution order:
// 1. LoggingMiddleware (before)
// 2. CORSMiddleware (before)
// 3. AuthMiddleware (before)
// 4. Controller action
// 5. AuthMiddleware (after)
// 6. CORSMiddleware (after)
// 7. LoggingMiddleware (after)
```

## Creating Custom Middleware

### Step 1: Create Middleware Class

```php
<?php

namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class CustomMiddleware extends Middleware
{
  public function handle(Request $request, callable $next): Response
  {
    // Before logic
    $this->log('Before controller');
    
    // Continue to next middleware/handler
    $response = $next($request);
    
    // After logic
    $this->log('After controller');
    
    return $response;
  }
  
  private function log(string $message): void
  {
    // Custom logging logic
  }
}
```

### Step 2: Register Middleware

In `Router::registerDefaultMiddleware()`:
```php
$this->routeMiddleware = [
  'auth' => AuthMiddleware::class,
  'cors' => CORSMiddleware::class,
  'logging' => LoggingMiddleware::class,
  'custom' => CustomMiddleware::class, // Add here
];
```

### Step 3: Use Middleware

```php
// Apply to route
$this->get('test', 'TestController', 'index', ['custom']);

// Or use class name directly
$this->get('test', 'TestController', 'index', [CustomMiddleware::class]);
```

## Security Considerations

### Sensitive Data Sanitization

LoggingMiddleware automatically redacts:
- Passwords
- API keys
- Tokens
- Secrets

### CORS Configuration

- Use specific origins in production (not `*`)
- Limit allowed methods to what's needed
- Be careful with `allowCredentials`

### Authentication

- AuthMiddleware checks session for `user_id`
- Returns appropriate response based on request type (JSON vs HTML)
- Consider rate limiting for auth endpoints

## Testing

Middleware tests are located in `tests/Unit/MiddlewareTest.php`:

- Request creation and parsing
- Response creation and headers
- AuthMiddleware authentication check
- CORSMiddleware preflight handling
- LoggingMiddleware request logging
- Middleware chain execution order

## Future Enhancements

### Planned Middleware

1. **RateLimitMiddleware** - Limit requests per IP/user
2. **ThrottleMiddleware** - Slow down repeated requests
3. **CacheMiddleware** - Cache responses
4. **SecurityHeadersMiddleware** - Add security headers
5. **LocaleMiddleware** - Handle internationalization

### Advanced Features

- Middleware priority/weight
- Conditional middleware execution
- Middleware parameters
- Async middleware support

## API Reference

### Middleware Class

```php
abstract class Middleware
{
  abstract public function handle(Request $request, callable $next): Response;
  
  public static function run(
    Request $request,
    array $middlewares,
    callable $finalHandler
  ): Response;
}
```

### Request Class

```php
class Request
{
  public function getMethod(): string;
  public function getUri(): string;
  public function getHeader(string $name): ?string;
  public function getHeaders(): array;
  public function getQuery(): array;
  public function getQueryParam(string $name, $default = null);
  public function getPost(): array;
  public function getPostParam(string $name, $default = null);
  public function getJsonBody(): array;
  public function getBody(): ?string;
  public function getRouteParams(): array;
  public function getRouteParam(string $name, $default = null);
  public function isAjax(): bool;
  public function wantsJson(): bool;
  public function isSecure(): bool;
  public function getClientIp(): string;
  public function getUserAgent(): ?string;
}
```

### Response Class

```php
class Response
{
  public function setStatusCode(int $code): self;
  public function getStatusCode(): int;
  public function setHeader(string $name, string $value): self;
  public function getHeaders(): array;
  public function setBody(string $body): self;
  public function getBody(): ?string;
  public function setJson(array $data): self;
  public function isJson(): bool;
  public function json(array $data, int $statusCode = 200): void;
  public function success(array $data = [], string $message = 'Success', int $statusCode = 200): void;
  public function error(string $message = 'An error occurred', int $statusCode = 400, array $details = []): void;
  public function redirect(string $url, int $statusCode = 302): void;
  public function html(string $html, int $statusCode = 200): void;
  public function text(string $text, int $statusCode = 200): void;
  public function send(): void;
}
```

---

**Implementation Date:** 2026-03-30
**Status:** ✅ Complete
**Version:** 1.0.0