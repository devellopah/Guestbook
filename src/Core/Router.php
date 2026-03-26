<?php

namespace Core;

use Services\MessageService;
use Services\UserService;

class Router
{
  private array $routes = [];
  private string $basePath = '';
  private Container $container;

  public function __construct(Container $container)
  {
    $this->container = $container;

    // Define all application routes
    $this->get('', 'MessageController', 'index');
    $this->get('/', 'MessageController', 'index');
    $this->post('/', 'MessageController', 'index');
    $this->get('messages', 'MessageController', 'index');
    $this->get('login', 'UserController', 'login');
    $this->get('register', 'UserController', 'register');
    $this->post('login', 'UserController', 'login');
    $this->post('register', 'UserController', 'register');
    $this->get('logout', 'UserController', 'logout');

    // Define API routes
    $this->get('api/v1/messages', 'API\MessagesController', 'index');
    $this->post('api/v1/messages', 'API\MessagesController', 'create');
    $this->get('api/v1/messages/{id}', 'API\MessagesController', 'show');
    $this->put('api/v1/messages/{id}', 'API\MessagesController', 'update');
    $this->delete('api/v1/messages/{id}', 'API\MessagesController', 'delete');
    $this->patch('api/v1/messages/{id}/status', 'API\MessagesController', 'toggleStatus');

    $this->post('api/v1/auth/login', 'API\AuthController', 'login');
    $this->post('api/v1/auth/logout', 'API\AuthController', 'logout');
    $this->post('api/v1/auth/register', 'API\AuthController', 'register');
    $this->get('api/v1/auth/me', 'API\AuthController', 'me');

    // Test routes for error handling (only in development)
    if ($this->isDevelopmentMode()) {
      $this->get('test/error', 'TestController', 'triggerError');
      $this->get('test/notfound', 'TestController', 'triggerNotFound');
      $this->get('test/badrequest', 'TestController', 'triggerBadRequest');
      $this->get('test/fatal', 'TestController', 'triggerFatalError');
    }
  }

  private function isDevelopmentMode(): bool
  {
    // Check environment variable
    if (getenv('APP_ENV') === 'development') {
      return true;
    }

    // Check .env file
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
      $envContent = file_get_contents($envFile);
      if (strpos($envContent, 'APP_ENV=development') !== false) {
        return true;
      }
    }

    return false;
  }

  public function get(string $path, string $controller, string $method): self
  {
    $this->routes['GET'][$this->normalizePath($path)] = ['controller' => $controller, 'method' => $method];
    return $this;
  }

  public function post(string $path, string $controller, string $method): self
  {
    $this->routes['POST'][$this->normalizePath($path)] = ['controller' => $controller, 'method' => $method];
    return $this;
  }

  public function put(string $path, string $controller, string $method): self
  {
    $this->routes['PUT'][$this->normalizePath($path)] = ['controller' => $controller, 'method' => $method];
    return $this;
  }

  public function delete(string $path, string $controller, string $method): self
  {
    $this->routes['DELETE'][$this->normalizePath($path)] = ['controller' => $controller, 'method' => $method];
    return $this;
  }

  public function patch(string $path, string $controller, string $method): self
  {
    $this->routes['PATCH'][$this->normalizePath($path)] = ['controller' => $controller, 'method' => $method];
    return $this;
  }

  public function dispatch(): void
  {
    $uri = $this->getCurrentUri();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Try exact match first
    if (isset($this->routes[$method][$uri])) {
      $this->callAction($this->routes[$method][$uri]);
      return;
    }

    // Try root path (empty URI should match empty route)
    if ($uri === '' && isset($this->routes[$method][''])) {
      $this->callAction($this->routes[$method]['']);
      return;
    }

    // No legacy routing support - only modern MVC routes
    http_response_code(404);
    echo "Page not found";
    return;
  }

  private function normalizePath(string $path): string
  {
    return trim($path, '/');
  }

  private function getCurrentUri(): string
  {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

    // Remove query string
    if (($pos = strpos($requestUri, '?')) !== false) {
      $requestUri = substr($requestUri, 0, $pos);
    }

    // Remove .php extension for modern routing
    $requestUri = str_replace('.php', '', $requestUri);

    // Normalize the path
    $path = trim($requestUri, '/');

    return $path;
  }

  private function callAction(array $route): void
  {
    $controllerClass = 'Controllers\\' . $route['controller'];
    $method = $route['method'];

    if (!class_exists($controllerClass)) {
      throw new \Exception("Controller {$controllerClass} not found");
    }

    // Create controller instance with proper dependency injection
    $controller = $this->createController($controllerClass);

    if (!method_exists($controller, $method)) {
      throw new \Exception("Method {$method} not found in {$controllerClass}");
    }

    $controller->$method();
  }

  private function createController(string $controllerClass): object
  {
    // For API controllers, we need to handle them differently since they don't extend BaseController
    if (strpos($controllerClass, 'API\\') !== false) {
      // API controllers don't need MessageService and UserService from BaseController
      // They handle their own service dependencies
      return new $controllerClass();
    }

    // For regular controllers, use the container to inject dependencies
    return $this->container->make($controllerClass);
  }
}
