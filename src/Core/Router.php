<?php

namespace Core;

class Router
{
  private array $routes = [];
  private string $basePath = '';

  public function __construct()
  {
    $this->basePath = dirname($_SERVER['SCRIPT_NAME']);

    // Define modern MVC routes
    $this->get('', 'MessageController', 'index');
    $this->get('messages', 'MessageController', 'index');
    $this->get('login', 'UserController', 'login');
    $this->get('register', 'UserController', 'register');
    $this->post('login', 'UserController', 'login');
    $this->post('register', 'UserController', 'register');
    $this->get('logout', 'UserController', 'logout');
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
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Remove query string
    if (strpos($requestUri, '?') !== false) {
      $requestUri = explode('?', $requestUri)[0];
    }

    // Remove base path
    if ($this->basePath && $this->basePath !== '/' && strpos($requestUri, $this->basePath) === 0) {
      $requestUri = substr($requestUri, strlen($this->basePath));
    }

    // Remove .php extension for modern routing
    $requestUri = str_replace('.php', '', $requestUri);

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
}
