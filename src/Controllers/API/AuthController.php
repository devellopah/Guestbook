<?php

namespace Controllers\API;

use Services\AuthService;
use Services\UserService;
use Exception;

class AuthController extends BaseApiController
{
  private AuthService $authService;
  protected UserService $userService;

  public function __construct()
  {
    parent::__construct();
    // Get services from the container since we're not using BaseController constructor
    global $container;
    if (isset($container)) {
      $this->userService = $container->make(\Services\UserService::class);
      $this->authService = new AuthService($this->userService);
    }
  }

  /**
   * POST /api/v1/auth/login - User login
   */
  public function login(): void
  {
    try {
      $data = $this->getJsonInput();
      $this->validateRequired($data, ['email', 'password']);

      $user = $this->authService->authenticate($data['email'], $data['password']);

      if ($user) {
        $this->authService->login($user);

        $responseData = [
          'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRoleId()
          ],
          'message' => 'Login successful'
        ];

        $this->successResponse($responseData);
      } else {
        $this->errorResponse('Invalid email or password', 401);
      }
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }

  /**
   * POST /api/v1/auth/logout - User logout
   */
  public function logout(): void
  {
    try {
      if (!$this->checkAuth()) {
        $this->errorResponse('Not authenticated', 401);
        return;
      }

      $this->authService->logout();
      $this->successResponse([], 'Logout successful');
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 500);
    }
  }

  /**
   * POST /api/v1/auth/register - User registration
   */
  public function register(): void
  {
    try {
      $data = $this->getJsonInput();
      $this->validateRequired($data, ['name', 'email', 'password', 'password_confirmation']);

      if ($data['password'] !== $data['password_confirmation']) {
        $this->errorResponse('Passwords do not match', 400);
        return;
      }

      $user = $this->userService->createUser($data['name'], $data['email'], $data['password']);

      $responseData = [
        'user' => [
          'id' => $user->getId(),
          'name' => $user->getName(),
          'email' => $user->getEmail()
        ],
        'message' => 'Registration successful'
      ];

      $this->successResponse($responseData, 'User registered successfully', 201);
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }

  /**
   * GET /api/v1/auth/me - Get current user
   */
  public function me(): void
  {
    try {
      if (!$this->checkAuth()) {
        $this->errorResponse('Not authenticated', 401);
        return;
      }

      $user = $this->authService->getCurrentUser();

      if ($user) {
        $responseData = [
          'user' => [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRoleId()
          ]
        ];

        $this->successResponse($responseData);
      } else {
        $this->errorResponse('User not found', 404);
      }
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 500);
    }
  }
}
