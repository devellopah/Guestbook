<?php

namespace Core;

use Services\MessageService;
use Services\UserService;
use Middleware\AuthMiddleware;
use Middleware\LoggingMiddleware;
use Middleware\CORSMiddleware;

class Router
{
  private array $routes = [];
  private string $basePath = '';
  private Container $container;
  private array $globalMiddleware = [];
  private array $routeMiddleware = [];

  public function __construct(Container $container)
  {
    $this->container = $container;

    // Register default middleware
    $this->registerDefaultMiddleware();

    // Define all application routes
    $this->registerRoutes();
  }

  /**
   * Register default middleware
   */
  private function registerDefaultMiddleware(): void
  {
    // Global middleware applied to all routes
    $this->globalMiddleware = [
      LoggingMiddleware::class,
      CORSMiddleware::class,
    ];

    // Route-specific middleware
    $this->routeMiddleware = [
      'auth' => AuthMiddleware::class,
      'cors' => CORSMiddleware::class,
      'logging' => LoggingMiddleware::class,
    ];
  }

  /**
   * Register all application routes
   */
  private function registerRoutes(): void
  {
    // Web routes
    $this->get('', 'MessageController', 'index');
    $this->get('/', 'MessageController', 'index');
    $this->post('/', 'MessageController', 'index');
    $this->get('messages', 'MessageController', 'index');
    $this->get('login', 'UserController', 'login');
    $this->get('register', 'UserController', 'register');
    $this->post('login', 'UserController', 'login');
    $this->post('register', 'UserController', 'register');
    $this->get('logout', 'UserController', 'logout');

    // API routes with authentication middleware
    $this->group('api/v1', function () {
      // Auth routes
      $this->post('auth/login', 'API\AuthController', 'login');
      $this->post('auth/logout', 'API\AuthController', 'logout');
      $this->post('auth/register', 'API\AuthController', 'register');
      $this->get('auth/me', 'API\AuthController', 'me');

      // Protected message routes
      $this->get('messages', 'API\MessagesController', 'index');
      $this->post('messages', 'API\MessagesController', 'create', ['auth']);
      $this->get('messages/{id}', 'API\MessagesController', 'show');
      $this->put('messages/{id}', 'API\MessagesController', 'update', ['auth']);
      $this->delete('messages/{id}', 'API\MessagesController', 'delete', ['auth']);
      $this->patch('messages/{id}/status', 'API\MessagesController', 'toggleStatus', ['auth']);
    });

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

  /**
   * Register GET route
   */
  public function get(string $path, string $controller, string $method, array $middleware = []): self
  {
    $this->routes['GET'][$this->normalizePath($path)] = [
      'controller' => $controller,
      'method' => $method,
      'middleware' => $middleware
    ];
    return $this;
  }

  /**
   * Register POST route
   */
  public function post(string $path, string $controller, string $method, array $middleware = []): self
  {
    $this->routes['POST'][$this->normalizePath($path)] = [
      'controller' => $controller,
      'method' => $method,
      'middleware' => $middleware
    ];
    return $this;
  }

  /**
   * Register PUT route
   */
  public function put(string $path, string $controller, string $method, array $middleware = []): self
  {
    $this->routes['PUT'][$this->normalizePath($path)] = [
      'controller' => $controller,
      'method' => $method,
      'middleware' => $middleware
    ];
    return $this;
  }

  /**
   * Register DELETE route
   */
  public function delete(string $path, string $controller, string $method, array $middleware = []): self
  {
    $this->routes['DELETE'][$this->normalizePath($path)] = [
      'controller' => $controller,
      'method' => $method,
      'middleware' => $middleware
    ];
    return $this;
  }

  /**
   * Register PATCH route
   */
  public function patch(string $path, string $controller, string $method, array $middleware = []): self
  {
    $this->routes['PATCH'][$this->normalizePath($path)] = [
      'controller' => $controller,
      'method' => $method,
      'middleware' => $middleware
    ];
    return $this;
  }

  /**
   * Register route group with common prefix and middleware
   */
  public function group(string $prefix, callable $callback, array $middleware = []): void
  {
    $previousPrefix = $this->basePath;
    $this->basePath = $this->normalizePath($prefix);

    $callback();

    $this->basePath = $previousPrefix;
  }

  public function dispatch(): void
  {
    $request = new Request();
    $uri = $this->getCurrentUri();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Try exact match first
    if (isset($this->routes[$method][$uri])) {
      $route = $this->routes[$method][$uri];
      $this->executeWithMiddleware($request, $route);
      return;
    }

    // Try root path (empty URI should match empty route)
    if ($uri === '' && isset($this->routes[$method][''])) {
      $route = $this->routes[$method][''];
      $this->executeWithMiddleware($request, $route);
      return;
    }

    // Try to match routes with parameters
    foreach ($this->routes[$method] ?? [] as $routePath => $route) {
      if ($this->matchRoute($routePath, $uri)) {
        $params = $this->extractRouteParams($routePath, $uri);
        $request->setRouteParams($params);
        $this->executeWithMiddleware($request, $route);
        return;
      }
    }

    // No route found
    $this->handleNotFound($request);
  }

  /**
   * Execute route with middleware chain
   */
  private function executeWithMiddleware(Request $request, array $route): void
  {
    // Combine global and route-specific middleware
    $middleware = array_merge($this->globalMiddleware, $route['middleware'] ?? []);

    // Resolve middleware classes
    $middlewareClasses = [];
    foreach ($middleware as $middlewareName) {
      if (isset($this->routeMiddleware[$middlewareName])) {
        $middlewareClasses[] = $this->routeMiddleware[$middlewareName];
      } elseif (class_exists($middlewareName)) {
        $middlewareClasses[] = $middlewareName;
      }
    }

    // Create final handler (controller action)
    $finalHandler = function (Request $request) use ($route) {
      $this->callAction($route, $request);
    };

    // Execute middleware chain
    $response = Middleware::run($request, $middlewareClasses, $finalHandler);

    // Send response
    $response->send();
  }

  /**
   * Call controller action
   */
  private function callAction(array $route, Request $request = null): Response
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

    // Pass request to controller method if it accepts it
    $methodReflection = new \ReflectionMethod($controller, $method);
    $parameters = $methodReflection->getParameters();

    if (count($parameters) > 0 && $parameters[0]->getType() && $parameters[0]->getType()->getName() === Request::class) {
      $controller->$method($request);
    } else {
      $controller->$method();
    }

    // Return empty response (controller handles output)
    return Response::create();
  }

  /**
   * Match route with parameters
   */
  private function matchRoute(string $routePath, string $uri): bool
  {
    // Convert route pattern to regex
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
    $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';

    return preg_match($pattern, $uri);
  }

  /**
   * Extract route parameters
   */
  private function extractRouteParams(string $routePath, string $uri): array
  {
    $params = [];

    // Extract parameter names from route
    preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
    $paramNames = $paramNames[1];

    // Extract parameter values from URI
    $pattern = '/^' . str_replace('/', '\/', preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath)) . '$/';
    preg_match($pattern, $uri, $paramValues);
    array_shift($paramValues); // Remove full match

    // Combine names and values
    foreach ($paramNames as $index => $name) {
      $params[$name] = $paramValues[$index] ?? null;
    }

    return $params;
  }

  /**
   * Handle 404 not found
   */
  private function handleNotFound(Request $request): void
  {
    $response = Response::create();

    if ($request->wantsJson() || $request->isAjax()) {
      $response->error('Page not found', 404);
    } else {
      http_response_code(404);
      echo "Page not found";
    }
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
