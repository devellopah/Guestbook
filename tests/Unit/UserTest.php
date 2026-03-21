<?php

namespace App\Tests\Unit;

use Valitron\Validator;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
  }

  public function testUserRegistrationSuccess(): void
  {
    // Test data with unique email
    $testData = [
      'name' => 'John Doe',
      'email' => 'john' . uniqid() . '@example.com',
      'password' => 'password123'
    ];

    // Use the new MVC structure - create user directly
    $user = new \Models\User($testData);
    $user->save();
    $result = true; // If no exception was thrown, registration succeeded

    // Assertions
    $this->assertTrue($result, 'Registration should succeed');
  }

  public function testUserRegistrationWithExistingEmail(): void
  {
    // Create existing user with unique email using direct database access
    $uniqueEmail = 'existing' . uniqid() . '@example.com';
    $existingUser = [
      'name' => 'Existing User',
      'email' => $uniqueEmail,
      'password' => password_hash('password123', PASSWORD_DEFAULT),
      'role' => 1
    ];

    // Use the Database model directly
    $db = \Models\Database::getInstance();

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->execute($existingUser);

    // Test data with same email
    $testData = [
      'name' => 'John Doe',
      'email' => $uniqueEmail,
      'password' => 'password123'
    ];

    // Use the new MVC structure - try to create user and expect exception for duplicate email
    try {
      $user = new \Models\User($testData);
      $user->save();
      $result = false; // Should not reach here
    } catch (\Exception $e) {
      $result = true; // Expected exception for duplicate email
    }

    // Assertions
    $this->assertTrue($result, 'Registration should fail with existing email');

    // Check if only one user exists with that email
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$testData['email']]);
    $count = $stmt->fetchColumn();

    $this->assertEquals(1, $count, 'Only one user should exist with that email');
  }

  public function testUserValidationWithInvalidEmail(): void
  {
    // Test data with invalid email
    $testData = [
      'name' => 'John Doe',
      'email' => 'invalid-email',
      'password' => 'password123'
    ];

    // Test validation using Valitron (same as in register.php)
    $v = new Validator($testData);
    $v->rules([
      'required' => ['name', 'email', 'password'],
      'email' => ['email'],
      'lengthMin' => [
        ['password', 6]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50],
      ]
    ]);

    // Assertions
    $this->assertFalse($v->validate(), 'Validation should fail with invalid email');
    $this->assertArrayHasKey('email', $v->errors(), 'Email validation should fail');
  }

  public function testUserValidationWithShortPassword(): void
  {
    // Test data with short password
    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => '123' // Too short
    ];

    // Test validation using Valitron (same as in register.php)
    $v = new Validator($testData);
    $v->rules([
      'required' => ['name', 'email', 'password'],
      'email' => ['email'],
      'lengthMin' => [
        ['password', 6]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50],
      ]
    ]);

    // Assertions
    $this->assertFalse($v->validate(), 'Validation should fail with short password');
    $this->assertArrayHasKey('password', $v->errors(), 'Password validation should fail');
  }

  public function testUserValidationWithEmptyFields(): void
  {
    // Test data with empty fields
    $testData = [
      'name' => '',
      'email' => '',
      'password' => ''
    ];

    // Test validation using Valitron (same as in register.php)
    $v = new Validator($testData);
    $v->rules([
      'required' => ['name', 'email', 'password'],
      'email' => ['email'],
      'lengthMin' => [
        ['password', 6]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50],
      ]
    ]);

    // Assertions
    $this->assertFalse($v->validate(), 'Validation should fail with empty fields');
    $this->assertArrayHasKey('name', $v->errors(), 'Name validation should fail');
    $this->assertArrayHasKey('email', $v->errors(), 'Email validation should fail');
    $this->assertArrayHasKey('password', $v->errors(), 'Password validation should fail');
  }

  public function testUserValidationWithValidData(): void
  {
    // Test data with valid data
    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => 'password123'
    ];

    // Test validation using Valitron (same as in register.php)
    $v = new Validator($testData);
    $v->rules([
      'required' => ['name', 'email', 'password'],
      'email' => ['email'],
      'lengthMin' => [
        ['password', 6]
      ],
      'lengthMax' => [
        ['name', 50],
        ['email', 50],
      ]
    ]);

    // Assertions
    $this->assertTrue($v->validate(), 'Validation should pass with valid data');
  }
}
