<?php

namespace Models;

use Exception;
use Valitron\Validator;

abstract class BaseModel
{
  protected ?int $id = null;
  protected string $table;
  protected array $fillable = [];
  protected array $hidden = [];

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

  abstract public function validate(): array;

  public function save(): bool
  {
    $errors = $this->validate();
    if (!empty($errors)) {
      throw new Exception(implode(', ', array_map(function ($field, $messages) {
        return reset($messages);
      }, array_keys($errors), $errors)));
    }

    try {
      return $this->id === null ? $this->create() : $this->update();
    } catch (Exception $e) {
      error_log(get_class($this) . " save error: " . $e->getMessage());
      throw new Exception('Unable to save ' . strtolower(basename(str_replace('\\', '/', get_class($this)))) . '. Please try again later.');
    }
  }

  abstract protected function create(): bool;
  abstract protected function update(): bool;

  public static function findById(int $id): ?self
  {
    try {
      $stmt = Database::query("SELECT * FROM " . static::$table . " WHERE id = ?", [$id]);
      $row = $stmt->fetch();

      if ($row) {
        return new static($row);
      }

      return null;
    } catch (Exception $e) {
      error_log(get_class() . " findById error: " . $e->getMessage());
      return null;
    }
  }

  public function delete(): bool
  {
    if ($this->id === null) {
      return false;
    }

    try {
      $stmt = Database::query("DELETE FROM " . static::$table . " WHERE id = ?", [$this->id]);
      return $stmt->rowCount() > 0;
    } catch (Exception $e) {
      error_log(get_class($this) . " delete error: " . $e->getMessage());
      return false;
    }
  }

  // Getters/setters
  public function getId(): ?int
  {
    return $this->id;
  }

  public function setId(int $id): void
  {
    $this->id = $id;
  }

  protected function getTableName(): string
  {
    return $this->table;
  }

  protected function getFillableFields(): array
  {
    return $this->fillable;
  }
}
