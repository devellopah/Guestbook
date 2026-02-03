<?php

namespace Models;

use Exception;
use Valitron\Validator;
use Models\Database;

class User
{
  private ?int $id = null;
  private string $name = '';
  private string $email = '';
  private string $password = '';
  private int $role = 1;
  private ?string $created_at = null;

  public function __construct(array $data = [])
  {
    if (!empty($data)) {
      $this->fill($data);
    }
  }

  public function fill(array $data): void
  {
    foreach ($data as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }

  public function validate(): array
  {
    $data = [
      'name' => $this->name,
      'email' => $this->email,
    ];

    if ($this->id === null) {
      // Validation for new users (password required)
      $data['password'] = $this->password;
    }

    $v = new Validator($data);
    $v->rule('required', ['name', 'email']);
    $v->rule('email', 'email');
    $v->rule('lengthMin', 'name', 2);
    $v->rule('lengthMax', 'name', 50);
    $v->rule('lengthMax', 'email', 50);

    if ($this->id === null) {
      $v->rule('required', 'password');
      $v->rule('lengthMin', 'password', 6);
    }

    if (!$v->validate()) {
      return $v->errors();
    }

    return [];
  }

  public function save(): bool
  {
    $errors = $this->validate();
    if (!empty($errors)) {
      throw new Exception(implode(', ', array_map(function ($field, $messages) {
        return ucfirst($field) . ': ' . implode(', ', $messages);
      }, array_keys($errors), $errors)));
    }

    // Check if email already exists (for new users)
    if ($this->id === null && $this->emailExists($this->email)) {
      throw new Exception('This email is already taken');
    }

    try {
      if ($this->id === null) {
        return $this->create();
      } else {
        return $this->update();
      }
    } catch (Exception $e) {
      error_log("User save error: " . $e->getMessage());
      throw new Exception('Unable to save user. Please try again later.');
    }
  }

  private function create(): bool
  {
    $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
    $params = [
      'name' => $this->name,
      'email' => $this->email,
      'password' => $hashedPassword,
      'role' => $this->role
    ];

    $stmt = Database::query($sql, $params);

    if ($stmt->rowCount() > 0) {
      $this->id = (int) Database::lastInsertId();
      return true;
    }

    return false;
  }

  private function update(): bool
  {
    $sql = "UPDATE users SET name = :name, email = :email";
    $params = [
      'id' => $this->id,
      'name' => $this->name,
      'email' => $this->email
    ];

    // Only update password if it's provided and not empty
    if (!empty($this->password)) {
      $sql .= ", password = :password";
      $params['password'] = password_hash($this->password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";

    $stmt = Database::query($sql, $params);
    return $stmt->rowCount() > 0;
  }

  public function authenticate(string $password): bool
  {
    if (empty($this->password)) {
      return false;
    }

    return password_verify($password, $this->password);
  }

  public static function findByEmail(string $email): ?self
  {
    try {
      $stmt = Database::query("SELECT * FROM users WHERE email = ?", [$email]);
      $row = $stmt->fetch();

      if ($row) {
        return new self($row);
      }

      return null;
    } catch (Exception $e) {
      error_log("User findByEmail error: " . $e->getMessage());
      return null;
    }
  }

  public static function findById(int $id): ?self
  {
    try {
      $stmt = Database::query("SELECT * FROM users WHERE id = ?", [$id]);
      $row = $stmt->fetch();

      if ($row) {
        return new self($row);
      }

      return null;
    } catch (Exception $e) {
      error_log("User findById error: " . $e->getMessage());
      return null;
    }
  }

  public static function emailExists(string $email): bool
  {
    try {
      $stmt = Database::query("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
      return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
      error_log("User emailExists error: " . $e->getMessage());
      return false;
    }
  }

  public static function getAll(int $limit = 10, int $offset = 0): array
  {
    try {
      $stmt = Database::query("SELECT * FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset", [
        'limit' => $limit,
        'offset' => $offset
      ]);
      return $stmt->fetchAll();
    } catch (Exception $e) {
      error_log("User getAll error: " . $e->getMessage());
      return [];
    }
  }

  public function delete(): bool
  {
    if ($this->id === null) {
      return false;
    }

    try {
      $stmt = Database::query("DELETE FROM users WHERE id = ?", [$this->id]);
      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      error_log("User delete error: " . $e->getMessage());
      return false;
    }
  }

  // Getters
  public function getId(): ?int
  {
    return $this->id;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getRole(): int
  {
    return $this->role;
  }

  public function getCreatedAt(): ?string
  {
    return $this->created_at;
  }

  // Setters
  public function setId(int $id): void
  {
    $this->id = $id;
  }

  public function setName(string $name): void
  {
    $this->name = $name;
  }

  public function setEmail(string $email): void
  {
    $this->email = $email;
  }

  public function setPassword(string $password): void
  {
    $this->password = $password;
  }

  public function setRole(int $role): void
  {
    $this->role = $role;
  }
}
