<?php

namespace Services;

use Valitron\Validator;
use Exception;

class ValidationService extends BaseService
{
  private array $customRules = [];

  public function __construct()
  {
    parent::__construct();
    $this->registerCustomRules();
  }

  /**
   * Register custom validation rules
   */
  private function registerCustomRules(): void
  {
    // Custom rule for profanity filtering
    Validator::addRule('no_profanity', function ($field, $value, array $params, array $fields) {
      return !$this->containsProfanity($value);
    }, 'The {field} contains inappropriate language');

    // Custom rule for email uniqueness
    Validator::addRule('unique_email', function ($field, $value, array $params, array $fields) {
      return !$this->emailExists($value, $params[0] ?? null);
    }, 'This email is already registered');

    // Custom rule for username uniqueness
    Validator::addRule('unique_username', function ($field, $value, array $params, array $fields) {
      return !$this->usernameExists($value, $params[0] ?? null);
    }, 'This username is already taken');

    // Custom rule for valid role
    Validator::addRule('valid_role', function ($field, $value, array $params, array $fields) {
      return in_array($value, [0, 1, 2]); // USER, MODERATOR, ADMIN
    }, 'Invalid role specified');
  }

  /**
   * Validate user registration data
   */
  public function validateUserRegistration(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['name', 'email', 'password'],
      'email' => ['email'],
      'lengthMin' => [
        ['name', 2],
        ['password', 6]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50],
        ['password', 100]
      ],
      'unique_email' => ['email'],
      'unique_username' => ['name']
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate user login data
   */
  public function validateUserLogin(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['email', 'password'],
      'email' => ['email'],
      'lengthMax' => [
        ['email', 50]
      ]
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate message creation data
   */
  public function validateMessageCreation(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['message'],
      'lengthMin' => [
        ['message', 1]
      ],
      'lengthMax' => [
        ['message', 1000]
      ],
      'no_profanity' => ['message']
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate message update data
   */
  public function validateMessageUpdate(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['message'],
      'lengthMin' => [
        ['message', 1]
      ],
      'lengthMax' => [
        ['message', 1000]
      ],
      'no_profanity' => ['message']
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate user update data
   */
  public function validateUserUpdate(array $data, ?int $userId = null): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['name', 'email'],
      'email' => ['email'],
      'lengthMin' => [
        ['name', 2]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50]
      ],
      'unique_email' => ['email', [$userId]],
      'unique_username' => ['name', [$userId]]
    ]);

    // Optional password validation
    if (!empty($data['password'])) {
      $v->rule('lengthMin', 'password', 6);
      $v->rule('lengthMax', 'password', 100);
    }

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate role assignment
   */
  public function validateRoleAssignment(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'required' => ['role'],
      'valid_role' => ['role']
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Validate pagination parameters
   */
  public function validatePagination(array $data): ValidationResult
  {
    $v = new Validator($data);

    $v->rules([
      'integer' => ['page', 'perPage'],
      'min' => [
        ['page', 1],
        ['perPage', 1]
      ],
      'max' => [
        ['perPage', 100]
      ]
    ]);

    return new ValidationResult($v->validate(), $v->errors());
  }

  /**
   * Check if text contains profanity
   */
  private function containsProfanity(string $text): bool
  {
    $profanityList = [
      'badword1',
      'badword2',
      'badword3', // Add actual profanity words
      'stupid',
      'idiot',
      'hate' // Examples
    ];

    $text = strtolower($text);

    foreach ($profanityList as $word) {
      if (stripos($text, $word) !== false) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if email exists in database
   */
  private function emailExists(string $email, ?int $excludeUserId = null): bool
  {
    // Skip database check if db is not available or not connected (e.g., during testing)
    if (!$this->db || !$this->isDatabaseConnected()) {
      return false;
    }

    try {
      $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
      $params = [$email];

      if ($excludeUserId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeUserId;
      }

      $stmt = $this->db->query($sql, $params);
      return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
      $this->log('Email existence check failed', ['email' => $email, 'error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Check if username exists in database
   */
  private function usernameExists(string $username, ?int $excludeUserId = null): bool
  {
    // Skip database check if db is not available or not connected (e.g., during testing)
    if (!$this->db || !$this->isDatabaseConnected()) {
      return false;
    }

    try {
      $sql = "SELECT COUNT(*) FROM users WHERE name = ?";
      $params = [$username];

      if ($excludeUserId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeUserId;
      }

      $stmt = $this->db->query($sql, $params);
      return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
      $this->log('Username existence check failed', ['username' => $username, 'error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Check if database is connected and available
   */
  private function isDatabaseConnected(): bool
  {
    try {
      // Try a simple query to test connection
      $this->db->query("SELECT 1");
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  /**
   * Get validation rules for a specific scenario
   */
  public function getValidationRules(string $scenario): array
  {
    $rules = [
      'user_registration' => [
        'required' => ['name', 'email', 'password'],
        'email' => ['email'],
        'lengthMin' => [
          ['name', 2],
          ['password', 6]
        ],
        'lengthMax' => [
          ['name', 50],
          ['email', 50],
          ['password', 100]
        ],
        'unique_email' => ['email'],
        'unique_username' => ['name']
      ],
      'user_login' => [
        'required' => ['email', 'password'],
        'email' => ['email'],
        'lengthMax' => [
          ['email', 50]
        ]
      ],
      'message_creation' => [
        'required' => ['message'],
        'lengthMin' => [
          ['message', 1]
        ],
        'lengthMax' => [
          ['message', 1000]
        ],
        'no_profanity' => ['message']
      ]
    ];

    return $rules[$scenario] ?? [];
  }
}

/**
 * Validation result wrapper class
 */
class ValidationResult
{
  private bool $isValid;
  private array $errors;

  public function __construct(bool $isValid, array $errors = [])
  {
    $this->isValid = $isValid;
    $this->errors = $errors;
  }

  public function isValid(): bool
  {
    return $this->isValid;
  }

  public function getErrors(): array
  {
    return $this->errors;
  }

  public function getFirstError(?string $field = null): ?string
  {
    if ($field && isset($this->errors[$field])) {
      return reset($this->errors[$field]);
    }

    foreach ($this->errors as $fieldErrors) {
      if (!empty($fieldErrors)) {
        return reset($fieldErrors);
      }
    }

    return null;
  }

  public function hasErrors(): bool
  {
    return !$this->isValid || !empty($this->errors);
  }

  public function getErrorString(): string
  {
    $errorStrings = [];
    foreach ($this->errors as $field => $fieldErrors) {
      foreach ($fieldErrors as $error) {
        $errorStrings[] = $error;
      }
    }
    return implode(', ', $errorStrings);
  }
}
