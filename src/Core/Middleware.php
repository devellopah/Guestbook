<?php

namespace Core;

/**
 * Abstract base class for all middleware
 */
abstract class Middleware
{
  /**
   * Handle the incoming request
   * 
   * @param Request $request The incoming request
   * @param callable $next The next middleware/handler in the chain
   * @return Response The response
   */
  abstract public function handle(Request $request, callable $next): Response;

  /**
   * Run the middleware chain
   * 
   * @param Request $request The incoming request
   * @param array $middlewares Array of middleware classes
   * @param callable $finalHandler The final handler
   * @return Response The response
   */
  public static function run(Request $request, array $middlewares, callable $finalHandler): Response
  {
    $pipeline = array_reduce(
      array_reverse($middlewares),
      function ($next, $middleware) {
        return function (Request $request) use ($middleware, $next) {
          $instance = new $middleware();
          $response = $instance->handle($request, $next);
          return $response instanceof Response ? $response : Response::create();
        };
      },
      function (Request $request) use ($finalHandler) {
        $response = $finalHandler($request);
        return $response instanceof Response ? $response : Response::create();
      }
    );

    $result = $pipeline($request);
    return $result instanceof Response ? $result : Response::create();
  }
}
