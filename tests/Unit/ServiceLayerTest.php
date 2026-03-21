<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\MessageService;
use Services\UserService;
use Services\AuthService;
use Services\Interfaces\MessageServiceInterface;
use Services\Interfaces\UserServiceInterface;
use Models\User;
use Models\Message;
use Core\Application;

class ServiceLayerTest extends TestCase
{
  private Application $app;

  protected function setUp(): void
  {
    parent::setUp();
    $this->app = Application::getInstance();
  }

  public function testMessageServiceImplementsInterface(): void
  {
    $messageService = $this->app->make(MessageService::class);
    $this->assertInstanceOf(MessageServiceInterface::class, $messageService);
  }

  public function testUserServiceImplementsInterface(): void
  {
    $userService = $this->app->make(UserService::class);
    $this->assertInstanceOf(UserServiceInterface::class, $userService);
  }

  public function testAuthServiceExists(): void
  {
    $authService = $this->app->make(AuthService::class);
    $this->assertInstanceOf(AuthService::class, $authService);
  }

  public function testMessageServiceHasBaseServiceMethods(): void
  {
    $messageService = $this->app->make(MessageService::class);

    // Check if it has methods from BaseService
    $this->assertTrue(method_exists($messageService, 'log'));
    $this->assertTrue(method_exists($messageService, 'beginTransaction'));
    $this->assertTrue(method_exists($messageService, 'commit'));
    $this->assertTrue(method_exists($messageService, 'rollback'));
  }

  public function testUserServiceHasBaseServiceMethods(): void
  {
    $userService = $this->app->make(UserService::class);

    // Check if it has methods from BaseService
    $this->assertTrue(method_exists($userService, 'log'));
    $this->assertTrue(method_exists($userService, 'beginTransaction'));
    $this->assertTrue(method_exists($userService, 'commit'));
    $this->assertTrue(method_exists($userService, 'rollback'));
  }

  public function testMessageServiceInterfaceMethods(): void
  {
    $messageService = $this->app->make(MessageService::class);

    // Check if all interface methods exist
    $this->assertTrue(method_exists($messageService, 'getMessages'));
    $this->assertTrue(method_exists($messageService, 'createMessage'));
    $this->assertTrue(method_exists($messageService, 'updateMessage'));
    $this->assertTrue(method_exists($messageService, 'toggleMessageStatus'));
    $this->assertTrue(method_exists($messageService, 'getMessageById'));
    $this->assertTrue(method_exists($messageService, 'deleteMessage'));
  }

  public function testUserServiceInterfaceMethods(): void
  {
    $userService = $this->app->make(UserService::class);

    // Check if all interface methods exist
    $this->assertTrue(method_exists($userService, 'getUserById'));
    $this->assertTrue(method_exists($userService, 'getUserByUsername'));
    $this->assertTrue(method_exists($userService, 'getUserByEmail'));
    $this->assertTrue(method_exists($userService, 'createUser'));
    $this->assertTrue(method_exists($userService, 'authenticate'));
    $this->assertTrue(method_exists($userService, 'updateUserRole'));
    $this->assertTrue(method_exists($userService, 'deleteUser'));
    $this->assertTrue(method_exists($userService, 'emailExists'));
    $this->assertTrue(method_exists($userService, 'usernameExists'));
  }

  public function testAuthServiceMethods(): void
  {
    $authService = $this->app->make(AuthService::class);

    // Check if all methods exist
    $this->assertTrue(method_exists($authService, 'authenticate'));
    $this->assertTrue(method_exists($authService, 'isUserLoggedIn'));
    $this->assertTrue(method_exists($authService, 'getCurrentUser'));
    $this->assertTrue(method_exists($authService, 'login'));
    $this->assertTrue(method_exists($authService, 'logout'));
    $this->assertTrue(method_exists($authService, 'hasPermission'));
    $this->assertTrue(method_exists($authService, 'isAdmin'));
    $this->assertTrue(method_exists($authService, 'isModerator'));
  }
}
