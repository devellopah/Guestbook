<?php

namespace Controllers\API;

use Core\BaseApiController;
use Core\Request;
use Core\Response;
use Services\JwtService;
use Services\AuthService;
use Services\UserService;

class JwtController extends BaseApiController
{
  protected JwtService $jwtService;
  protected AuthService $authService;
  protected UserService $userService;

  public function __construct(BaseApiController $baseApiController)
  {
    parent::__construct(
      $baseApiController->messageService,
      $baseApiController->userService,
      $baseApiController->request,
      $baseApiController->response
    );
    $this->jwtService = new JwtService();
    $this->authService = new AuthService($baseApiController->userService);
    $this->userService = $baseApiController->userService;
  }

  /**
   * Login with email and password
   */
  public function login(): void
  {
    try {
      $data = $this->request->getJsonBody();

      if (!isset($data['email']) || !isset($data['password'])) {
        $this->response->error('Email and password are required', 400);
        return;
      }

      // Authenticate user
      $user = $this->authService->authenticate($data['email'], $data['password']);

      if (!$user) {
        $this->response->error('Invalid email or password', 401);
        return;
      }

      // Generate token pair
      $tokens = $this->jwtService->generateTokenPair([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
      ]);

      // Set tokens in response
      $this->jwtService->setTokenInResponse($this->response, $tokens['access_token'], 'access');
      $this->jwtService->setTokenInResponse($this->response, $tokens['refresh_token'], 'refresh');

      // Return token response
      $this->response->success($tokens, 'Login successful');
    } catch (\Exception $e) {
      $this->response->error('Login failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Refresh access token using refresh token
   */
  public function refresh(): void
  {
    try {
      $data = $this->request->getJsonBody();

      if (!isset($data['refresh_token'])) {
        $this->response->error('Refresh token is required', 400);
        return;
      }

      // Refresh access token
      $newTokens = $this->jwtService->refreshAccessToken($data['refresh_token']);

      if (!$newTokens) {
        $this->response->error('Invalid or expired refresh token', 401);
        return;
      }

      // Set new tokens in response
      $this->jwtService->setTokenInResponse($this->response, $newTokens['access_token'], 'access');
      $this->jwtService->setTokenInResponse($this->response, $newTokens['refresh_token'], 'refresh');

      // Return new token response
      $this->response->success($newTokens, 'Token refreshed successfully');
    } catch (\Exception $e) {
      $this->response->error('Token refresh failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Logout - blacklist token
   */
  public function logout(): void
  {
    try {
      $token = $this->jwtService->getTokenFromRequest($this->request);

      if ($token) {
        $this->jwtService->blacklistToken($token);
      }

      // Clear cookies
      $this->response->setHeader('Set-Cookie', 'access_token=; Path=/; HttpOnly; Max-Age=0; Secure; SameSite=Strict');
      $this->response->setHeader('Set-Cookie', 'refresh_token=; Path=/; HttpOnly; Max-Age=0; Secure; SameSite=Strict');

      $this->response->success([], 'Logout successful');
    } catch (\Exception $e) {
      $this->response->error('Logout failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Get current user info
   */
  public function me(): void
  {
    try {
      $token = $this->jwtService->getTokenFromRequest($this->request);

      if (!$token) {
        $this->response->error('Token not provided', 401);
        return;
      }

      // Validate token
      $payload = $this->jwtService->validateToken($token, true);

      if (!$payload) {
        $this->response->error('Invalid or expired token', 401);
        return;
      }

      // Get user info
      $user = $this->userService->findUserById($payload['user_id']);

      if (!$user) {
        $this->response->error('User not found', 404);
        return;
      }

      // Return user info
      $this->response->success([
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'created_at' => $user['created_at']
      ], 'User info retrieved successfully');
    } catch (\Exception $e) {
      $this->response->error('Failed to get user info: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Register new user and get token
   */
  public function register(): void
  {
    try {
      $data = $this->request->getJsonBody();

      if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
        $this->response->error('Email, password, and name are required', 400);
        return;
      }

      // Register user
      $user = $this->userService->createUserJwt([
        'email' => $data['email'],
        'password' => $data['password'],
        'name' => $data['name'],
        'role' => 'user' // Default role
      ]);

      if (!$user) {
        $this->response->error('Registration failed', 500);
        return;
      }

      // Generate token pair
      $tokens = $this->jwtService->generateTokenPair([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
      ]);

      // Set tokens in response
      $this->jwtService->setTokenInResponse($this->response, $tokens['access_token'], 'access');
      $this->jwtService->setTokenInResponse($this->response, $tokens['refresh_token'], 'refresh');

      // Return registration response
      $this->response->success($tokens, 'Registration successful');
    } catch (\Exception $e) {
      $this->response->error('Registration failed: ' . $e->getMessage(), 500);
    }
  }

  /**
   * Validate token
   */
  public function validate(): void
  {
    try {
      $token = $this->jwtService->getTokenFromRequest($this->request);

      if (!$token) {
        $this->response->error('Token not provided', 401);
        return;
      }

      // Validate token
      $payload = $this->jwtService->validateToken($token, true);

      if (!$payload) {
        $this->response->error('Invalid or expired token', 401);
        return;
      }

      // Return token metadata
      $metadata = $this->jwtService->getTokenMetadata($token);

      $this->response->success($metadata, 'Token is valid');
    } catch (\Exception $e) {
      $this->response->error('Token validation failed: ' . $e->getMessage(), 500);
    }
  }
}
