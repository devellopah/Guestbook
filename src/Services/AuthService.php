<?php

namespace Services;

use Models\User;
use Services\Interfaces\UserServiceInterface;
use Exception;

class AuthService extends BaseService
{
  private UserServiceInterface $userService;

  public function __construct(UserServiceInterface $userService)
  {
    parent::__construct();
    $this->userService = $userService;
  }

  /**
   * Аутентифицировать пользователя
   */
  public function authenticate(string $email, string $password): ?User
  {
    $this->log('Authentication attempt', ['email' => $email]);

    if (empty($email) || empty($password)) {
      throw new Exception('Email and password are required');
    }

    $user = $this->userService->authenticate($email, $password);

    if ($user) {
      $this->log('Authentication successful', ['user_id' => $user->getId(), 'email' => $email]);
    } else {
      $this->log('Authentication failed', ['email' => $email]);
    }

    return $user;
  }

  /**
   * Проверить, авторизован ли пользователь
   */
  public function isUserLoggedIn(): bool
  {
    return isset($_SESSION['user_id']);
  }

  /**
   * Получить текущего пользователя
   */
  public function getCurrentUser(): ?User
  {
    if (!$this->isUserLoggedIn()) {
      return null;
    }

    return $this->userService->getUserById($_SESSION['user_id']);
  }

  /**
   * Авторизовать пользователя в сессии
   */
  public function login(User $user): void
  {
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['user_email'] = $user->getEmail();
    $_SESSION['user_role'] = $user->getRoleId();

    $this->log('User logged in', ['user_id' => $user->getId(), 'email' => $user->getEmail()]);
  }

  /**
   * Выйти из системы
   */
  public function logout(): void
  {
    if ($this->isUserLoggedIn()) {
      $this->log('User logged out', ['user_id' => $_SESSION['user_id']]);
    }

    session_unset();
    session_destroy();
  }

  /**
   * Проверить права доступа
   */
  public function hasPermission(int $requiredRole): bool
  {
    if (!$this->isUserLoggedIn()) {
      return false;
    }

    $currentUser = $this->getCurrentUser();
    if (!$currentUser) {
      return false;
    }

    return $currentUser->getRoleId() >= $requiredRole;
  }

  /**
   * Проверить, является ли пользователь администратором
   */
  public function isAdmin(): bool
  {
    return $this->hasPermission(2); // ADMIN role
  }

  /**
   * Проверить, является ли пользователь модератором или администратором
   */
  public function isModerator(): bool
  {
    return $this->hasPermission(1); // MODERATOR role
  }
}
