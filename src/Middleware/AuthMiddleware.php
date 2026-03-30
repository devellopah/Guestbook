<?php

namespace Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Authentication middleware for protecting routes
 */
class AuthMiddleware extends Middleware
{
  /**
   * Handle the incoming request
   */
  public function handle(Request $request, callable $next): Response
  {
    // Check if user is authenticated
    if (!$this->isAuthenticated()) {
      $response = Response::create();

      if ($request->wantsJson() || $request->isAjax()) {
        $response->error('Authentication required', 401);
      } else {
        // Redirect to login page for web requests
        $response->redirect('/login');
      }

      return $response;
    }

    // User is authenticated, continue to next middleware/handler
    return $next($request);
  }

  /**
   * Check if user is authenticated
   */
  private function isAuthenticated(): bool
  {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
  }

  /**
   * Check if user has specific role
   */
  public static function hasRole(string $role): bool
  {
    if (!isset($_SESSION['user_role'])) {
      return false;
    }

    $userRole = $_SESSION['user_role'];

    // Admin has access to everything
    if ($userRole === 'admin') {
      return true;
    }

    return $userRole === $role;
  }

  /**
   * Check if user is admin
   */
  public static function isAdmin(): bool
  {
    return self::hasRole('admin');
  }

  /**
   * Get current user ID
   */
  public static function getUserId(): ?int
  {
    return $_SESSION['user_id'] ?? null;
  }

  /**
   * Get current user email
   */
  public static function getUserEmail(): ?string
  {
    return $_SESSION['user_email'] ?? null;
  }

  /**
   * Get current user role
   */
  public static function getUserRole(): ?string
  {
    return $_SESSION['user_role'] ?? null;
  }
}
