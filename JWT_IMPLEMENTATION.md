# JWT Authentication Implementation

## Overview

This document describes the JWT authentication implementation for the Guestbook application. The JWT system provides secure token-based authentication for API endpoints.

## Architecture

### Core Components

1. **JwtService** (`src/Services/JwtService.php`)
   - JWT token generation and validation
   - Access and refresh token management
   - Token metadata and validation
   - Token blacklisting for logout

2. **JwtMiddleware** (`src/Middleware/JwtMiddleware.php`)
   - JWT token validation for API routes
   - User information injection into requests
   - Role-based access control
   - Error handling for invalid tokens

3. **JwtController** (`src/Controllers/API/JwtController.php`)
   - Login endpoint (email + password)
   - Token refresh endpoint
   - Logout endpoint
   - User info endpoint
   - Token validation endpoint
   - User registration endpoint

4. **Router Integration** (`src/Core/Router.php`)
   - JWT middleware registration
   - JWT-protected API routes
   - Token-based authentication for message endpoints

## JWT Service Features

### Token Generation

**Access Tokens:**
- 1-hour expiration (configurable)
- Contains user_id, email, role
- Issued at (iat), not before (nbf), expiration (exp)
- Issuer (iss) and audience (aud) claims

**Refresh Tokens:**
- 24-hour expiration (configurable)
- Used to obtain new access tokens
- Longer lifespan for security

**Token Pair Generation:**
```php
$tokens = $jwtService->generateTokenPair([
  'user_id' => $user['id'],
  'email' => $user['email'],
  'role' => $user['role']
]);
```

### Token Validation

**Validation Process:**
1. Extract token from Authorization header, cookie, or query parameter
2. Verify signature using secret key
3. Check expiration and not-before claims
4. Validate payload structure
5. Return user information if valid

**Validation Methods:**
- `validateToken()` - Validate and decode token
- `isValidToken()` - Check if token is valid
- `getTokenMetadata()` - Get token information
- `getTokenType()` - Determine token type (access/refresh)

### Token Management

**Token Refresh:**
- Use refresh token to obtain new access token
- Automatic token pair generation
- Expiration checking

**Token Blacklisting:**
- Mark tokens as invalid for logout
- Store blacklisted tokens (future implementation with Redis)
- Prevent token reuse after logout

**Token Extraction:**
- From Authorization: Bearer header
- From access_token cookie
- From query parameter

## JWT Middleware

### Authentication Process

1. Extract token from request
2. Validate token signature and claims
3. Add user information to request parameters
4. Continue to next middleware/controller
5. Return 401 error if token invalid

### User Information Injection

**Injected Parameters:**
- `user_id` - Authenticated user ID
- `email` - User email address
- `role` - User role (admin/user)

**Access Methods:**
```php
// In controllers
$userId = $request->getRouteParam('user_id');
$email = $request->getRouteParam('email');
$role = $request->getRouteParam('role');

// Static methods
$userId = JwtMiddleware::getUserId($request);
$email = JwtMiddleware::getUserEmail($request);
$role = JwtMiddleware::getUserRole($request);
```

### Role-Based Access Control

**Role Checking:**
```php
// Check if user has specific role
if (JwtMiddleware::hasRole($request, 'admin')) {
  // Admin-only functionality
}

// Check if user is admin
if (JwtMiddleware::isAdmin($request)) {
  // Admin functionality
}
```

## API Endpoints

### Authentication Endpoints

**POST /api/v1/auth/login**
- Email + password authentication
- Returns access and refresh tokens
- Sets tokens in Authorization header and cookies

**POST /api/v1/auth/register**
- User registration with email, password, name
- Returns access and refresh tokens
- Default role: 'user'

**POST /api/v1/auth/refresh**
- Refresh access token using refresh token
- Returns new token pair
- Checks refresh token validity

**POST /api/v1/auth/logout**
- Blacklist current token
- Clear access and refresh cookies
- Invalidate session

**GET /api/v1/auth/me**
- Get current user information
- Requires valid access token
- Returns user ID, email, name, role, created_at

**GET /api/v1/auth/validate**
- Validate token and return metadata
- Check token expiration
- Return token information

### Protected Message Endpoints

**GET /api/v1/messages**
- List messages (JWT auth required)
- Returns paginated message list

**POST /api/v1/messages**
- Create new message (JWT auth required)
- Requires authentication

**GET /api/v1/messages/{id}**
- Get specific message
- Public endpoint (no auth required)

**PUT /api/v1/messages/{id}**
- Update message (JWT auth required)
- Requires authentication

**DELETE /api/v1/messages/{id}**
- Delete message (JWT auth required)
- Requires authentication

**PATCH /api/v1/messages/{id}/status**
- Toggle message status (JWT auth required)
- Requires authentication

## Configuration

### Environment Variables

**JWT Configuration:**
```env
JWT_SECRET=your-secret-key-change-in-production
JWT_ALGORITHM=HS256
JWT_ACCESS_TTL=3600
JWT_REFRESH_TTL=86400
```

**Application Configuration:**
```env
APP_URL=http://localhost
APP_DOMAIN=localhost
APP_ENV=development
```

### Security Considerations

**Token Security:**
- Use strong secret keys in production
- Store secrets in environment variables
- Use HTTPS in production
- Set secure cookie flags
- Implement token blacklisting

**Access Control:**
- JWT middleware protects sensitive endpoints
- Role-based access control
- Token expiration and refresh
- Input validation and sanitization

**Cookie Security:**
- HTTP-only cookies
- Secure flag in production
- SameSite=Strict
- Domain-specific cookies

## Usage Examples

### Login and Get Messages

```php
// Login
$response = $client->post('/api/v1/auth/login', [
  'json' => [
    'email' => 'user@example.com',
    'password' => 'password123'
  ]
]);

$tokens = $response->json();
$accessToken = $tokens['access_token'];

// Get messages with JWT
$response = $client->get('/api/v1/messages', [
  'headers' => [
    'Authorization' => "Bearer $accessToken"
  ]
]);
```

### Token Refresh

```php
// Refresh token
$response = $client->post('/api/v1/auth/refresh', [
  'json' => [
    'refresh_token' => $refreshToken
  ]
]);

$newTokens = $response->json();
$accessToken = $newTokens['access_token'];
```

### Protected Endpoint

```php
// Create message with JWT
$response = $client->post('/api/v1/messages', [
  'headers' => [
    'Authorization' => "Bearer $accessToken"
  ],
  'json' => [
    'message' => 'Hello world!'
  ]
]);
```

## Testing

JWT functionality is tested through integration tests in `tests/Integration/APIIntegrationTest.php`.

## Future Enhancements

### Planned Features

1. **Token Revocation List** - Store blacklisted tokens in Redis
2. **Token Rotation** - Automatic token rotation on each request
3. **Multi-Device Support** - Per-device token management
4. **Token Analytics** - Track token usage and expiration
5. **Rate Limiting** - JWT-based rate limiting

### Advanced Features

- **OAuth Integration** - Support for OAuth providers
- **Multi-Factor Authentication** - 2FA support
- **Token Scopes** - Granular permission control
- **Token Auditing** - Comprehensive audit logging
- **Session Management** - Advanced session handling

## API Reference

### JwtService Class

```php
class JwtService extends BaseService
{
  public function generateAccessToken(array $payload): string;
  public function generateRefreshToken(array $payload): string;
  public function generateTokenPair(array $payload): array;
  public function validateToken(string $token, bool $isAccessToken = true): ?array;
  public function refreshAccessToken(string $refreshToken): ?array;
  public function getTokenMetadata(string $token): array;
  public function blacklistToken(string $token): bool;
  public function isValidToken(string $token, bool $isAccessToken = true): bool;
  public function getTokenFromRequest(Request $request): ?string;
  public function getTokenType(string $token): ?string;
  public function setTokenInResponse(Response $response, string $token, string $type = 'access'): Response;
}
```

### JwtMiddleware Class

```php
class JwtMiddleware extends Middleware
{
  public function handle(Request $request, callable $next): Response;
  public static function hasRole(Request $request, string $role): bool;
  public static function isAdmin(Request $request): bool;
  public static function getUserId(Request $request): ?int;
  public static function getUserEmail(Request $request): ?string;
  public static function getUserRole(Request $request): ?string;
}
```

### JwtController Class

```php
class JwtController extends BaseController
{
  public function login(): void;
  public function refresh(): void;
  public function logout(): void;
  public function me(): void;
  public function register(): void;
  public function validate(): void;
}
```

---

**Implementation Date:** 2026-04-05
**Status:** ✅ Complete
**Version:** 1.0.0