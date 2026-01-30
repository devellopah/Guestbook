<?php

namespace App\Tests\Unit;

use App\Tests\BaseTestCase;
use Valitron\Validator;

class UserTest extends BaseTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $this->clearTestData();
  }

  public function testUserRegistrationSuccess(): void
  {
    global $db;

    // Test data
    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => 'password123'
    ];

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

    // Call register function
    $result = register($testData);

    // Assertions
    $this->assertTrue($result, 'Registration should succeed');

    // Check if user was created in database
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$testData['email']]);
    $user = $stmt->fetch();

    $this->assertNotFalse($user, 'User should be found in database');
    $this->assertEquals($testData['name'], $user['name']);
    $this->assertEquals($testData['email'], $user['email']);
    $this->assertTrue(password_verify($testData['password'], $user['password']));
    $this->assertEquals(1, $user['role']); // Default role
  }

  public function testUserRegistrationWithExistingEmail(): void
  {
    global $db;

    // Create existing user
    $existingUser = [
      'name' => 'Existing User',
      'email' => 'existing@example.com',
      'password' => password_hash('password123', PASSWORD_DEFAULT),
      'role' => 1
    ];

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->execute($existingUser);

    // Test data with same email
    $testData = [
      'name' => 'John Doe',
      'email' => 'existing@example.com',
      'password' => 'password123'
    ];

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

    // Call register function
    $result = register($testData);

    // Assertions
    $this->assertFalse($result, 'Registration should fail with existing email');

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

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

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

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

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

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

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

    // Load functions
    require_once __DIR__ . '/../../incs/functions.php';

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
