<?php

namespace Core;

use Services\MessageService;
use Services\UserService;

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
