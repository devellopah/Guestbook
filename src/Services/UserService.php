<?php

namespace Services;

use Models\User;
use Exception;

class UserService
{
  public function getUserById(int $userId): ?User
  {
    return User::findById($userId);
  }

  public function getUserByUsername(string $username): ?User
  {
    return User::findByUsername($username);
  }

  public function getUserByEmail(string $email): ?User
  {
    return User::findByEmail($email);
  }

  public function createUser(string $username, string $email, string $password): User
  {
    // Check if user already exists
    if ($this->getUserByUsername($username)) {
      throw new Exception('Username already exists');
    }

    if ($this->getUserByEmail($email)) {
      throw new Exception('Email already exists');
    }

    $user = new User([
      'username' => $username,
      'email' => $email,
      'password' => password_hash($password, PASSWORD_DEFAULT),
      'role' => 0 // Default role
    ]);

    $user->save();
    return $user;
  }

  public function authenticate(string $email, string $password): ?User
  {
    $user = $this->getUserByEmail($email);

    if (!$user || !$user->authenticate($password)) {
      return null;
    }

    return $user;
  }

  public function updateUserRole(int $userId, int $role): User
  {
    $user = $this->getUserById($userId);

    if (!$user) {
      throw new Exception('User not found');
    }

    $user->setRole($role);
    $user->save();
    return $user;
  }

  public function deleteUser(int $userId): bool
  {
    $user = $this->getUserById($userId);

    if (!$user) {
      throw new Exception('User not found');
    }

    return $user->delete();
  }
}
