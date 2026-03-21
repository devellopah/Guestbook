<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Models\Database;

abstract class BaseTestCase extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    // Ensure database connection is available
    try {
      Database::getInstance();
    } catch (\Exception $e) {
      $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
    }
  }
}
