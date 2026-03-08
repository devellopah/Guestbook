<?php

namespace Controllers;

use Core\BaseController;

class TestController extends BaseController
{
  public function triggerError(): void
  {
    // This will trigger an exception for testing
    throw new \Exception("This is a test exception to demonstrate error handling");
  }

  public function triggerNotFound(): void
  {
    // This will trigger a 404-like error
    throw new \OutOfBoundsException("Resource not found");
  }

  public function triggerBadRequest(): void
  {
    // This will trigger a 400-like error
    throw new \InvalidArgumentException("Invalid request parameters");
  }

  public function triggerFatalError(): void
  {
    // This will trigger a fatal error
    trigger_error("This is a test fatal error", E_USER_ERROR);
  }
}
