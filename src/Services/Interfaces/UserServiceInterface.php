<?php

namespace Services\Interfaces;

use Models\User;

interface UserServiceInterface
{
  /**
   * Получить пользователя по ID
   */
  public function getUserById(int $userId): ?User;

  /**
   * Получить пользователя по имени
   */
  public function getUserByUsername(string $username): ?User;

  /**
   * Получить пользователя по email
   */
  public function getUserByEmail(string $email): ?User;

  /**
   * Создать нового пользователя
   */
  public function createUser(string $username, string $email, string $password): User;

  /**
   * Аутентифицировать пользователя
   */
  public function authenticate(string $email, string $password): ?User;

  /**
   * Обновить роль пользователя
   */
  public function updateUserRole(int $userId, int $role): User;

  /**
   * Удалить пользователя
   */
  public function deleteUser(int $userId): bool;

  /**
   * Проверить, существует ли email
   */
  public function emailExists(string $email): bool;

  /**
   * Проверить, существует ли username
   */
  public function usernameExists(string $username): bool;
}
