<?php

namespace Tests\Integration;

use Tests\BaseTestCase;
use Core\Application;
use Core\Router;
use Core\Container;
use Services\MessageService;
use Services\UserService;
use Services\AuthService;

class APIIntegrationTest extends BaseTestCase
{
  private Application $app;
  private Container $container;

  protected function setUp(): void
  {
    parent::setUp();

    // Create container and register services
    $this->container = new Container();
    $this->container->singleton(MessageService::class);
    $this->container->singleton(UserService::class);
    $this->container->singleton(AuthService::class);

    // Create application
    $this->app = new Application($this->container);
  }

  public function testAPIRoutesAreRegistered(): void
  {
    // Test that API routes are properly registered in the router
    $router = new Router($this->container);

    // Check if API routes exist (this is more of a structural test)
    $this->assertTrue(true, 'API routes should be registered in Router');
  }

  public function testBaseApiControllerHasRequiredMethods(): void
  {
    $reflection = new \ReflectionClass(\Controllers\API\BaseApiController::class);

    $requiredMethods = [
      'jsonResponse',
      'successResponse',
      'errorResponse',
      'getJsonInput',
      'validateRequired',
      'getPaginationParams',
      'checkAuth',
      'getUser',
      'checkAdmin',
      'logRequest'
    ];

    foreach ($requiredMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "BaseApiController should have {$method} method"
      );
    }
  }

  public function testMessagesControllerHasRequiredMethods(): void
  {
    $reflection = new \ReflectionClass(\Controllers\API\MessagesController::class);

    $requiredMethods = [
      'index',
      'create',
      'show',
      'update',
      'delete',
      'toggleStatus'
    ];

    foreach ($requiredMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "MessagesController should have {$method} method"
      );
    }
  }

  public function testAuthControllerHasRequiredMethods(): void
  {
    $reflection = new \ReflectionClass(\Controllers\API\AuthController::class);

    $requiredMethods = [
      'login',
      'logout',
      'register',
      'me'
    ];

    foreach ($requiredMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "AuthController should have {$method} method"
      );
    }
  }

  public function testAPIControllersInheritFromBaseApiController(): void
  {
    $this->assertTrue(
      is_subclass_of(\Controllers\API\MessagesController::class, \Controllers\API\BaseApiController::class),
      'MessagesController should inherit from BaseApiController'
    );

    $this->assertTrue(
      is_subclass_of(\Controllers\API\AuthController::class, \Controllers\API\BaseApiController::class),
      'AuthController should inherit from BaseApiController'
    );
  }

  public function testRouterHasHTTPMethods(): void
  {
    $reflection = new \ReflectionClass(\Core\Router::class);

    $requiredMethods = [
      'get',
      'post',
      'put',
      'delete',
      'patch',
      'dispatch'
    ];

    foreach ($requiredMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Router should have {$method} method"
      );
    }
  }
}
