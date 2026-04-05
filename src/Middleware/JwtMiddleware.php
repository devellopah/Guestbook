<?php

namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Services\JwtService;

/**
 * JWT middleware for API authentication
 */
class JwtMiddleware extends Middleware
{
  private JwtService $jwtService;

  public function __construct()
  {
    $this->jwtService = new JwtService();
  }

  /**
   * Handle the incoming request
   */
  public function handle(Request $request, callable $next): Response
  {
    // Get token from request
    $token = $this->jwtService->getTokenFromRequest($request);

    if (!$token) {
      $response = Response::create();
      $response->error('Token not provided', 401);
      return $response;
    }

    // Validate token
    $payload = $this->jwtService->validateToken($token, true);

    if (!$payload) {
      $response = Response::create();
      $response->error('Invalid or expired token', 401);
      return $response;
    }

    // Add user info to request
    $request->setRouteParams(array_merge($request->getRouteParams(), [
      'user_id' => $payload['user_id'],
      'email' => $payload['email'],
      'role' => $payload['role']
    ]));

    // User is authenticated, continue to next middleware/handler
    return $next($request);
  }

  /**
   * Check if user has specific role
   */
  public static function hasRole(Request $request, string $role): bool
  {
    $userRole = $request->getRouteParam('role');
    return $userRole === $role;
  }

  /**
   * Check if user is admin
   */
  public static function isAdmin(Request $request): bool
  {
    return self::hasRole($request, 'admin');
  }

  /**
   * Get current user ID
   */
  public static function getUserId(Request $request): ?int
  {
    return $request->getRouteParam('user_id');
  }

  /**
   * Get current user email
   */
  public static function getUserEmail(Request $request): ?string
  {
    return $request->getRouteParam('email');
  }

  /**
   * Get current user role
   */
  public static function getUserRole(Request $request): ?string
  {
    return $request->getRouteParam('role');
  }
}
