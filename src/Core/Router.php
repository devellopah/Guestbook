<?php

namespace Core;

class Router
{
  private array $routes = [];
  private string $basePath = '';

  public function __construct()
  {
    $this->basePath = dirname($_SERVER['SCRIPT_NAME']);

    // Define routes
    $this->get('', 'MessageController', 'index');
    $this->get('index.php', 'MessageController', 'index');
    $this->get('login.php', 'UserController', 'login');
    $this->get('register.php', 'UserController', 'register');
    $this->post('login.php', 'UserController', 'login');
    $this->post('register.php', 'UserController', 'register');
    $this->get('index.php?controller=User&action=logout', 'UserController', 'logout');
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

    // Try root path
    if ($uri === '' && isset($this->routes[$method]['/'])) {
      $this->callAction($this->routes[$method]['/']);
      return;
    }

    // Check for query string controller/action (e.g., index.php?controller=User&action=logout)
    $controller = $_GET['controller'] ?? '';
    $action = $_GET['action'] ?? '';

    if ($controller && $action) {
      $controllerClass = 'Controllers\\' . $controller . 'Controller';
      if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
        $controller = new $controllerClass();
        $controller->$action();
        return;
      }
    }

    // Fallback to index.php routes for backward compatibility
    $this->handleLegacyRoutes();
  }

  private function normalizePath(string $path): string
  {
    return trim($path, '/');
  }

  private function getCurrentUri(): string
  {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Remove query string
    if (strpos($requestUri, '?') !== false) {
      $requestUri = explode('?', $requestUri)[0];
    }

    // Remove base path
    if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
      $requestUri = substr($requestUri, strlen($this->basePath));
    }

    return $this->normalizePath($requestUri);
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

  private function handleLegacyRoutes(): void
  {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');

    switch ($scriptName) {
      case 'index.php':
        $controller = new \Controllers\MessageController();
        $controller->index();
        break;
      case 'login.php':
        $controller = new \Controllers\UserController();
        $controller->login();
        break;
      case 'register.php':
        $controller = new \Controllers\UserController();
        $controller->register();
        break;
      default:
        http_response_code(404);
        echo "Page not found";
        break;
    }
  }
}
