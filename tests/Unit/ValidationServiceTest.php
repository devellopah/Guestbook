<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\ValidationService;
use Services\ValidationResult;

class ValidationServiceTest extends TestCase
{
  private ValidationService $validationService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->validationService = new ValidationService();
  }

  public function testUserRegistrationValidationSuccess(): void
  {
    $validData = [
      'name' => 'Test User',
      'email' => 'test@example.com',
      'password' => 'password123'
    ];

    $result = $this->validationService->validateUserRegistration($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    // Debug: print errors if validation fails
    if (!$result->isValid()) {
      print_r($result->getErrors());
    }
    $this->assertTrue($result->isValid(), 'Validation should pass for valid data');
    $this->assertEmpty($result->getErrors());
  }

  public function testUserRegistrationValidationWithEmptyFields(): void
  {
    $invalidData = [
      'name' => '',
      'email' => '',
      'password' => ''
    ];

    $result = $this->validationService->validateUserRegistration($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertNotEmpty($result->getErrors());
    $this->assertArrayHasKey('name', $result->getErrors());
    $this->assertArrayHasKey('email', $result->getErrors());
    $this->assertArrayHasKey('password', $result->getErrors());
  }

  public function testUserRegistrationValidationWithInvalidEmail(): void
  {
    $invalidData = [
      'name' => 'John Doe',
      'email' => 'invalid-email',
      'password' => 'password123'
    ];

    $result = $this->validationService->validateUserRegistration($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('email', $result->getErrors());
  }

  public function testUserRegistrationValidationWithShortPassword(): void
  {
    $invalidData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => '123'
    ];

    $result = $this->validationService->validateUserRegistration($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('password', $result->getErrors());
  }

  public function testUserRegistrationValidationWithLongName(): void
  {
    $invalidData = [
      'name' => str_repeat('a', 51), // 51 characters
      'email' => 'john@example.com',
      'password' => 'password123'
    ];

    $result = $this->validationService->validateUserRegistration($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('name', $result->getErrors());
  }

  public function testUserLoginValidationSuccess(): void
  {
    $validData = [
      'email' => 'john@example.com',
      'password' => 'password123'
    ];

    $result = $this->validationService->validateUserLogin($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testUserLoginValidationWithEmptyFields(): void
  {
    $invalidData = [
      'email' => '',
      'password' => ''
    ];

    $result = $this->validationService->validateUserLogin($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('email', $result->getErrors());
    $this->assertArrayHasKey('password', $result->getErrors());
  }

  public function testUserLoginValidationWithInvalidEmail(): void
  {
    $invalidData = [
      'email' => 'invalid-email',
      'password' => 'password123'
    ];

    $result = $this->validationService->validateUserLogin($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('email', $result->getErrors());
  }

  public function testMessageCreationValidationSuccess(): void
  {
    $validData = [
      'message' => 'This is a valid message'
    ];

    $result = $this->validationService->validateMessageCreation($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testMessageCreationValidationWithEmptyMessage(): void
  {
    $invalidData = [
      'message' => ''
    ];

    $result = $this->validationService->validateMessageCreation($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('message', $result->getErrors());
  }

  public function testMessageCreationValidationWithTooLongMessage(): void
  {
    $invalidData = [
      'message' => str_repeat('a', 1001) // 1001 characters
    ];

    $result = $this->validationService->validateMessageCreation($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('message', $result->getErrors());
  }

  public function testMessageUpdateValidationSuccess(): void
  {
    $validData = [
      'message' => 'This is an updated message'
    ];

    $result = $this->validationService->validateMessageUpdate($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testUserUpdateValidationSuccess(): void
  {
    $validData = [
      'name' => 'Jane Doe',
      'email' => 'jane@example.com'
    ];

    $result = $this->validationService->validateUserUpdate($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testUserUpdateValidationWithPassword(): void
  {
    $validData = [
      'name' => 'Jane Doe',
      'email' => 'jane@example.com',
      'password' => 'newpassword123'
    ];

    $result = $this->validationService->validateUserUpdate($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testUserUpdateValidationWithShortPassword(): void
  {
    $invalidData = [
      'name' => 'Jane Doe',
      'email' => 'jane@example.com',
      'password' => '123' // Too short
    ];

    $result = $this->validationService->validateUserUpdate($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('password', $result->getErrors());
  }

  public function testRoleAssignmentValidationSuccess(): void
  {
    $validData = [
      'role' => 1 // USER role
    ];

    $result = $this->validationService->validateRoleAssignment($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testRoleAssignmentValidationWithInvalidRole(): void
  {
    $invalidData = [
      'role' => 99 // Invalid role
    ];

    $result = $this->validationService->validateRoleAssignment($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('role', $result->getErrors());
  }

  public function testPaginationValidationSuccess(): void
  {
    $validData = [
      'page' => 1,
      'perPage' => 10
    ];

    $result = $this->validationService->validatePagination($validData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
  }

  public function testPaginationValidationWithInvalidPage(): void
  {
    $invalidData = [
      'page' => 0, // Invalid page number
      'perPage' => 10
    ];

    $result = $this->validationService->validatePagination($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('page', $result->getErrors());
  }

  public function testPaginationValidationWithInvalidPerPage(): void
  {
    $invalidData = [
      'page' => 1,
      'perPage' => 101 // Too high
    ];

    $result = $this->validationService->validatePagination($invalidData);

    $this->assertInstanceOf(ValidationResult::class, $result);
    $this->assertFalse($result->isValid());
    $this->assertArrayHasKey('perPage', $result->getErrors());
  }

  public function testValidationResultMethods(): void
  {
    $errors = [
      'name' => ['Name is required'],
      'email' => ['Email is invalid', 'Email is too long']
    ];

    $result = new ValidationResult(false, $errors);

    $this->assertFalse($result->isValid());
    $this->assertEquals($errors, $result->getErrors());
    $this->assertTrue($result->hasErrors());
    $this->assertEquals('Name is required', $result->getFirstError('name'));
    $this->assertEquals('Name is required', $result->getFirstError());
    $this->assertEquals('Name is required, Email is invalid, Email is too long', $result->getErrorString());
  }

  public function testValidationResultWithNoErrors(): void
  {
    $result = new ValidationResult(true, []);

    $this->assertTrue($result->isValid());
    $this->assertEmpty($result->getErrors());
    $this->assertFalse($result->hasErrors());
    $this->assertNull($result->getFirstError());
    $this->assertEmpty($result->getErrorString());
  }

  public function testGetValidationRules(): void
  {
    $userRegistrationRules = $this->validationService->getValidationRules('user_registration');
    $this->assertNotEmpty($userRegistrationRules);
    $this->assertArrayHasKey('required', $userRegistrationRules);
    $this->assertArrayHasKey('email', $userRegistrationRules);
    $this->assertArrayHasKey('lengthMin', $userRegistrationRules);
    $this->assertArrayHasKey('lengthMax', $userRegistrationRules);
    $this->assertArrayHasKey('unique_email', $userRegistrationRules);
    $this->assertArrayHasKey('unique_username', $userRegistrationRules);

    $userLoginRules = $this->validationService->getValidationRules('user_login');
    $this->assertNotEmpty($userLoginRules);
    $this->assertArrayHasKey('required', $userLoginRules);
    $this->assertArrayHasKey('email', $userLoginRules);
    $this->assertArrayHasKey('lengthMax', $userLoginRules);

    $messageCreationRules = $this->validationService->getValidationRules('message_creation');
    $this->assertNotEmpty($messageCreationRules);
    $this->assertArrayHasKey('required', $messageCreationRules);
    $this->assertArrayHasKey('lengthMin', $messageCreationRules);
    $this->assertArrayHasKey('lengthMax', $messageCreationRules);
    $this->assertArrayHasKey('no_profanity', $messageCreationRules);

    $invalidRules = $this->validationService->getValidationRules('invalid_scenario');
    $this->assertEmpty($invalidRules);
  }
}
