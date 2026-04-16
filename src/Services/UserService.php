<?php

namespace Services;

use Models\User;
use Exception;
use Services\Interfaces\UserServiceInterface;

class UserService extends BaseService implements UserServiceInterface
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

  public function emailExists(string $email): bool
  {
    return User::emailExists($email);
  }

  public function usernameExists(string $username): bool
  {
    return User::usernameExists($username);
  }

  // Additional methods for JWT controller
  public function findUserById(int $id): ?array
  {
    $user = $this->getUserById($id);
    return $user ? [
      'id' => $user->getId(),
      'email' => $user->getEmail(),
      'name' => $user->getName(),
      'role' => $user->getRole(),
      'created_at' => $user->getCreatedAt()
    ] : null;
  }

  public function createUserJwt(array $data): ?array
  {
    try {
      $user = $this->createUser(
        $data['name'],
        $data['email'],
        $data['password']
      );
      return [
        'id' => $user->getId(),
        'email' => $user->getEmail(),
        'name' => $user->getName(),
        'role' => $user->getRole(),
        'created_at' => $user->getCreatedAt()
      ];
    } catch (Exception $e) {
      return null;
    }
  }
}
