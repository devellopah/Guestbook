<?php

namespace Core;

use Services\MessageService;
use Services\UserService;
use Services\Interfaces\MessageServiceInterface;
use Services\Interfaces\UserServiceInterface;

class Application
{
  private static ?self $instance = null;
  private Container $container;

  private function __construct()
  {
    $this->container = new Container();
    $this->registerServices();
  }

  public static function getInstance(): self
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function getContainer(): Container
  {
    return $this->container;
  }

  private function registerServices(): void
  {
    // Register service interfaces to implementations
    $this->container->singleton(MessageServiceInterface::class, MessageService::class);
    $this->container->singleton(UserServiceInterface::class, UserService::class);

    // Register services as singletons
    $this->container->singleton(MessageService::class);
    $this->container->singleton(UserService::class);

    // Register models as singletons
    $this->container->singleton(\Models\Message::class);
    $this->container->singleton(\Models\User::class);
    $this->container->singleton(\Models\Database::class);

    // Register controllers as singletons
    $this->container->singleton(\Controllers\MessageController::class);
    $this->container->singleton(\Controllers\UserController::class);
    $this->container->singleton(\Controllers\TestController::class);

    // Register AuthService
    $this->container->singleton(\Services\AuthService::class);

    // Register CacheService
    $this->container->singleton(\Services\CacheService::class);
  }

  public function run(): void
  {
    // Initialize error handler
    ErrorHandler::init();

    // Initialize router with container
    $router = $this->container->make(Router::class);
    $router->dispatch();
  }

  public function make(string $class, array $parameters = []): object
  {
    return $this->container->make($class, $parameters);
  }

  public function call(callable $callback, array $parameters = []): mixed
  {
    return $this->container->call($callback, $parameters);
  }
}
