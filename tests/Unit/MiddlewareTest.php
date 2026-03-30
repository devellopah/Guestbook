<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Middleware\AuthMiddleware;
use Middleware\LoggingMiddleware;
use Middleware\CORSMiddleware;

class MiddlewareTest extends TestCase
{
  public function testRequestCreation(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test';

    $request = new Request();

    $this->assertEquals('GET', $request->getMethod());
    $this->assertEquals('/test', $request->getUri());
  }

  public function testResponseCreation(): void
  {
    $response = Response::create();

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testResponseSetStatusCode(): void
  {
    $response = Response::create();
    $response->setStatusCode(404);

    $this->assertEquals(404, $response->getStatusCode());
  }

  public function testResponseSetHeader(): void
  {
    $response = Response::create();
    $response->setHeader('X-Custom-Header', 'test-value');

    $headers = $response->getHeaders();
    $this->assertArrayHasKey('X-Custom-Header', $headers);
    $this->assertEquals('test-value', $headers['X-Custom-Header']);
  }

  public function testAuthMiddlewareIsAuthenticated(): void
  {
    // Test without session
    $_SESSION = [];

    $middleware = new AuthMiddleware();

    // Use reflection to test private method
    $reflection = new \ReflectionClass($middleware);
    $method = $reflection->getMethod('isAuthenticated');
    $method->setAccessible(true);

    $this->assertFalse($method->invoke($middleware));
  }

  public function testAuthMiddlewareIsAuthenticatedWithSession(): void
  {
    // Test with session
    $_SESSION['user_id'] = 123;

    $middleware = new AuthMiddleware();

    // Use reflection to test private method
    $reflection = new \ReflectionClass($middleware);
    $method = $reflection->getMethod('isAuthenticated');
    $method->setAccessible(true);

    $this->assertTrue($method->invoke($middleware));
  }

  public function testCORSMiddlewareHandlesPreflight(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
    $_SERVER['REQUEST_URI'] = '/api/test';
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';
    $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'POST';

    $request = new Request();
    $middleware = new CORSMiddleware();

    $response = $middleware->handle($request, function ($req) {
      return Response::create();
    });

    $this->assertEquals(204, $response->getStatusCode());
  }

  public function testLoggingMiddlewareLogsRequest(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/test';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $request = new Request();
    $middleware = new LoggingMiddleware();

    $executed = false;
    $response = $middleware->handle($request, function ($req) use (&$executed) {
      $executed = true;
      return Response::create();
    });

    $this->assertTrue($executed);
    $this->assertInstanceOf(Response::class, $response);
  }

  public function testMiddlewareChainExecution(): void
  {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test';

    $request = new Request();

    // Create mock middleware classes
    $middleware1Class = new class extends Middleware {
      public static array $executionOrder = [];

      public function handle(Request $request, callable $next): Response
      {
        self::$executionOrder[] = 'middleware1_before';
        $response = $next($request);
        self::$executionOrder[] = 'middleware1_after';
        return $response;
      }
    };

    $middleware2Class = new class extends Middleware {
      public static array $executionOrder = [];

      public function handle(Request $request, callable $next): Response
      {
        self::$executionOrder[] = 'middleware2_before';
        $response = $next($request);
        self::$executionOrder[] = 'middleware2_after';
        return $response;
      }
    };

    // Reset execution order
    get_class($middleware1Class)::$executionOrder = [];
    get_class($middleware2Class)::$executionOrder = [];

    $finalHandler = function (Request $request) use ($middleware1Class) {
      get_class($middleware1Class)::$executionOrder[] = 'final_handler';
      return Response::create();
    };

    Middleware::run($request, [
      get_class($middleware1Class),
      get_class($middleware2Class)
    ], $finalHandler);

    $this->assertEquals([
      'middleware1_before',
      'middleware2_before',
      'final_handler',
      'middleware2_after',
      'middleware1_after'
    ], get_class($middleware1Class)::$executionOrder);
  }
}
