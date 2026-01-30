<?php

namespace App\Tests\Integration;

use App\Tests\BaseTestCase;

class DatabaseTest extends BaseTestCase
{
  public function testDatabaseConnection(): void
  {
    global $db;

    // Test that we can execute a simple query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();

    $this->assertNotFalse($result, 'Database connection should work');
    $this->assertEquals(1, $result['test'], 'Simple query should return expected result');
  }

  public function testUsersTableExists(): void
  {
    global $db;

    // Test that users table exists and has expected structure
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll();

    $this->assertNotEmpty($columns, 'Users table should exist');

    // Check for expected columns
    $columnNames = array_column($columns, 'Field');
    $this->assertContains('id', $columnNames, 'Users table should have id column');
    $this->assertContains('name', $columnNames, 'Users table should have name column');
    $this->assertContains('email', $columnNames, 'Users table should have email column');
    $this->assertContains('password', $columnNames, 'Users table should have password column');
    $this->assertContains('role', $columnNames, 'Users table should have role column');
  }

  public function testMessagesTableExists(): void
  {
    global $db;

    // Test that messages table exists and has expected structure
    $stmt = $db->query("DESCRIBE messages");
    $columns = $stmt->fetchAll();

    $this->assertNotEmpty($columns, 'Messages table should exist');

    // Check for expected columns
    $columnNames = array_column($columns, 'Field');
    $this->assertContains('id', $columnNames, 'Messages table should have id column');
    $this->assertContains('user_id', $columnNames, 'Messages table should have user_id column');
    $this->assertContains('message', $columnNames, 'Messages table should have message column');
    $this->assertContains('status', $columnNames, 'Messages table should have status column');
    $this->assertContains('created_at', $columnNames, 'Messages table should have created_at column');
  }
}
