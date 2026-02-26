<?php

namespace Models;

use Exception;
use Valitron\Validator;

class Message
{
  private ?int $id = null;
  private ?int $user_id = null;
  private string $message = '';
  private int $status = 1;
  private ?string $created_at = null;
  private ?User $user = null;

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
    $v = new Validator([
      'message' => $this->message
    ]);

    $v->rule('required', 'message');
    $v->rule('lengthMin', 'message', 2);
    $v->rule('lengthMax', 'message', 1000);

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

    if ($this->user_id === null) {
      throw new Exception('User ID is required');
    }

    try {
      if ($this->id === null) {
        return $this->create();
      } else {
        return $this->update();
      }
    } catch (Exception $e) {
      error_log("Message save error: " . $e->getMessage());
      throw new Exception('Unable to save message. Please try again later.');
    }
  }

  private function create(): bool
  {
    $sql = "INSERT INTO messages (user_id, message, status) VALUES (:user_id, :message, :status)";
    $params = [
      'user_id' => $this->user_id,
      'message' => $this->message,
      'status' => $this->status
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
    $sql = "UPDATE messages SET message = :message, status = :status WHERE id = :id";
    $params = [
      'id' => $this->id,
      'message' => $this->message,
      'status' => $this->status
    ];

    $stmt = Database::query($sql, $params);
    return $stmt->rowCount() > 0;
  }

  public static function findById(int $id): ?self
  {
    try {
      $stmt = Database::query("
        SELECT m.*, u.name as user_name
        FROM messages m
        JOIN users u ON u.id = m.user_id
        WHERE m.id = ?
      ", [$id]);

      $row = $stmt->fetch();

      if ($row) {
        $message = new self($row);
        // Create user object for the message
        $user = new User([
          'id' => $row['user_id'],
          'name' => $row['user_name']
        ]);
        $message->setUser($user);
        return $message;
      }

      return null;
    } catch (Exception $e) {
      error_log("Message findById error: " . $e->getMessage());
      return null;
    }
  }

  public static function getAll(int $limit = 10, int $offset = 0, bool $onlyActive = true): array
  {
    try {
      $where = $onlyActive ? 'WHERE m.status = 1' : '';
      $sql = "
        SELECT m.*, u.name as user_name, DATE_FORMAT(m.created_at, '%d.%m.%Y %H:%i') AS created_at_formatted
        FROM messages m
        JOIN users u ON u.id = m.user_id
        {$where}
        ORDER BY m.id DESC
        LIMIT :limit OFFSET :offset
      ";

      $stmt = Database::query($sql, [
        'limit' => $limit,
        'offset' => $offset
      ]);

      $messages = [];
      foreach ($stmt->fetchAll() as $row) {
        $message = new self($row);
        $user = new User([
          'id' => $row['user_id'],
          'name' => $row['user_name']
        ]);
        $message->setUser($user);
        $messages[] = $message;
      }

      return $messages;
    } catch (Exception $e) {
      error_log("Message getAll error: " . $e->getMessage());
      return [];
    }
  }

  public static function getCount(bool $onlyActive = true): int
  {
    try {
      $where = $onlyActive ? 'WHERE status = 1' : '';
      $sql = "SELECT COUNT(*) FROM messages {$where}";
      $stmt = Database::query($sql);
      return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("Message getCount error: " . $e->getMessage());
      return 0;
    }
  }

  public function delete(): bool
  {
    if ($this->id === null) {
      return false;
    }

    try {
      $stmt = Database::query("DELETE FROM messages WHERE id = ?", [$this->id]);
      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      error_log("Message delete error: " . $e->getMessage());
      return false;
    }
  }

  public function toggleStatus(): bool
  {
    if ($this->id === null) {
      return false;
    }

    try {
      $this->status = $this->status ? 0 : 1;
      $stmt = Database::query("UPDATE messages SET status = :status WHERE id = :id", [
        'status' => $this->status,
        'id' => $this->id
      ]);
      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      error_log("Message toggleStatus error: " . $e->getMessage());
      return false;
    }
  }

  // Getters
  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUserId(): ?int
  {
    return $this->user_id;
  }

  public function getMessage(): string
  {
    return $this->message;
  }

  public function getStatus(): int
  {
    return $this->status;
  }

  public function getCreatedAt(): ?string
  {
    return $this->created_at;
  }

  public function getUser(): ?User
  {
    return $this->user;
  }

  // Setters
  public function setId(int $id): void
  {
    $this->id = $id;
  }

  public function setUserId(int $user_id): void
  {
    $this->user_id = $user_id;
  }

  public function setMessage(string $message): void
  {
    $this->message = $message;
  }

  public function setStatus(int $status): void
  {
    $this->status = $status;
  }

  public function setCreatedAt(string $created_at): void
  {
    $this->created_at = $created_at;
  }

  public function setUser(User $user): void
  {
    $this->user = $user;
  }
}
