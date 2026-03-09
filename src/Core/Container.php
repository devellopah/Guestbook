<?php

namespace Core;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
  private array $bindings = [];
  private array $instances = [];
  private array $singletons = [];

  /**
   * Bind an interface to a concrete implementation
   */
  public function bind(string $abstract, ?string $concrete = null, bool $singleton = false): void
  {
    if ($concrete === null) {
      $concrete = $abstract;
    }

    $this->bindings[$abstract] = $concrete;
    if ($singleton) {
      $this->singletons[$abstract] = true;
    }
  }

  /**
   * Bind a singleton (shared instance)
   */
  public function singleton(string $abstract, ?string $concrete = null): void
  {
    $this->bind($abstract, $concrete, true);
  }

  /**
   * Bind an instance (already created object)
   */
  public function instance(string $abstract, object $instance): void
  {
    $this->instances[$abstract] = $instance;
  }

  /**
   * Resolve a class with automatic dependency injection
   */
  public function make(string $abstract, array $parameters = []): object
  {
    // Return existing instance if it's a singleton
    if (isset($this->instances[$abstract])) {
      return $this->instances[$abstract];
    }

    // Get the concrete class
    $concrete = $this->getConcrete($abstract);

    // Create the instance
    $instance = $this->build($concrete, $parameters);

    // Store as singleton if needed
    if (isset($this->singletons[$abstract])) {
      $this->instances[$abstract] = $instance;
    }

    return $instance;
  }

  /**
   * Get the concrete class for an abstract
   */
  protected function getConcrete(string $abstract): string
  {
    if (isset($this->bindings[$abstract])) {
      return $this->bindings[$abstract];
    }

    return $abstract;
  }

  /**
   * Build an instance using reflection
   */
  protected function build(string $concrete, array $parameters = []): object
  {
    try {
      $reflector = new ReflectionClass($concrete);

      // Check if class is instantiable
      if (!$reflector->isInstantiable()) {
        throw new \Exception("Class {$concrete} is not instantiable");
      }

      // Get constructor
      $constructor = $reflector->getConstructor();

      // If no constructor, create instance directly
      if (is_null($constructor)) {
        return new $concrete;
      }

      // Get constructor parameters
      $dependencies = $constructor->getParameters();

      // Resolve dependencies
      $instances = $this->resolveDependencies($dependencies, $parameters);

      // Create instance with resolved dependencies
      return $reflector->newInstanceArgs($instances);
    } catch (ReflectionException $e) {
      throw new \Exception("Failed to build {$concrete}: " . $e->getMessage());
    }
  }

  /**
   * Resolve constructor dependencies
   */
  protected function resolveDependencies(array $parameters, array $providedParameters = []): array
  {
    $dependencies = [];

    foreach ($parameters as $parameter) {
      // Check if parameter was provided
      if (isset($providedParameters[$parameter->getName()])) {
        $dependencies[] = $providedParameters[$parameter->getName()];
        continue;
      }

      // Check if parameter has a default value
      if ($parameter->isDefaultValueAvailable()) {
        $dependencies[] = $parameter->getDefaultValue();
        continue;
      }

      // Get type information
      $type = $parameter->getType();

      // If dependency is a class, resolve it
      if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
        $dependencies[] = $this->make($type->getName());
      } else {
        // Primitive type without default value - this is an error
        throw new \Exception("Cannot resolve parameter {$parameter->getName()} in {$parameter->getDeclaringClass()->getName()}");
      }
    }

    return $dependencies;
  }

  /**
   * Check if a binding exists
   */
  public function has(string $abstract): bool
  {
    return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
  }

  /**
   * Call a method with dependency injection
   */
  public function call(callable $callback, array $parameters = []): mixed
  {
    if (is_array($callback)) {
      // Method callback [class, method]
      list($class, $method) = $callback;
      $instance = is_object($class) ? $class : $this->make($class);
      $reflection = new \ReflectionMethod($instance, $method);
    } else {
      // Function callback
      $reflection = new \ReflectionFunction($callback);
    }

    $dependencies = $this->resolveDependencies($reflection->getParameters(), $parameters);

    return $reflection->invokeArgs($instance ?? null, $dependencies);
  }

  /**
   * Get all registered bindings
   */
  public function getBindings(): array
  {
    return $this->bindings;
  }

  /**
   * Clear all bindings and instances
   */
  public function flush(): void
  {
    $this->bindings = [];
    $this->instances = [];
    $this->singletons = [];
  }
}
