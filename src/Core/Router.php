<?php

namespace Core;

class Router
{
  private array $routes = [];
  private string $basePath = '';

  public function __construct()
  {
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

    $controller = new $controllerClass();

    if (!method_exists($controller, $method)) {
      throw new \Exception("Method {$method} not found in {$controllerClass}");
    }

    $controller->$method();
  }
}
