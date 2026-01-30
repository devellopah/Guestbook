<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
  protected static $testDb;
  protected static $originalDb;

  public static function setUpBeforeClass(): void
  {
    // Store original database connection
    global $db;
    self::$originalDb = $db;

    // Create test database connection
    self::createTestDatabase();
  }

  public static function tearDownAfterClass(): void
  {
    // Restore original database connection
    global $db;
    $db = self::$originalDb;

    // Clean up test database
    self::dropTestDatabase();
  }

  protected static function createTestDatabase(): void
  {
    $testDbConfig = [
      'host' => $_ENV['TEST_DB_HOST'] ?? 'db',
      'user' => $_ENV['TEST_DB_USER'] ?? 'root',
      'password' => $_ENV['TEST_DB_PASSWORD'] ?? 'password',
      'db_name' => $_ENV['TEST_DB_NAME'] ?? 'guestbook_test',
    ];

    $db_options = [
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ];

    $dsn = "mysql:host={$testDbConfig['host']};charset=utf8";

    try {
      $testDb = new \PDO($dsn, $testDbConfig['user'], $testDbConfig['password'], $db_options);

      // Create test database
      $testDb->exec("CREATE DATABASE IF NOT EXISTS {$testDbConfig['db_name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

      // Switch to test database
      $testDb->exec("USE {$testDbConfig['db_name']}");

      // Create tables
      self::createTestTables($testDb);

      // Set global test database
      global $db;
      $db = $testDb;
    } catch (\PDOException $e) {
      throw new \Exception("Failed to create test database: " . $e->getMessage());
    }
  }

  protected static function dropTestDatabase(): void
  {
    if (self::$testDb) {
      try {
        $testDbConfig = [
          'host' => $_ENV['TEST_DB_HOST'] ?? 'db',
          'user' => $_ENV['TEST_DB_USER'] ?? 'root',
          'password' => $_ENV['TEST_DB_PASSWORD'] ?? 'password',
          'db_name' => $_ENV['TEST_DB_NAME'] ?? 'guestbook_test',
        ];

        self::$testDb->exec("DROP DATABASE IF EXISTS {$testDbConfig['db_name']}");
      } catch (\PDOException $e) {
        // Ignore errors during cleanup
      }
    }
  }

  protected static function createTestTables(\PDO $db): void
  {
    // Create users table
    $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                email VARCHAR(50) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role TINYINT(4) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

    // Create messages table
    $db->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NOT NULL,
                message TEXT NOT NULL,
                status TINYINT(4) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                CONSTRAINT messages_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
  }

  protected function clearTestData(): void
  {
    global $db;
    try {
      // Disable foreign key checks temporarily
      $db->exec("SET FOREIGN_KEY_CHECKS = 0");

      // Clear data in correct order (child table first)
      $db->exec("DELETE FROM messages");
      $db->exec("DELETE FROM users");

      // Reset auto-increment counters
      $db->exec("ALTER TABLE messages AUTO_INCREMENT = 1");
      $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");

      // Re-enable foreign key checks
      $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (\PDOException $e) {
      // If foreign key checks are not supported, try regular truncate
      $db->exec("DELETE FROM messages");
      $db->exec("DELETE FROM users");
      $db->exec("ALTER TABLE messages AUTO_INCREMENT = 1");
      $db->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    }
  }

  protected function createTestUser(array $data = []): int
  {
    global $db;
    $defaultData = [
      'name' => 'Test User',
      'email' => 'test@example.com',
      'password' => password_hash('password123', PASSWORD_DEFAULT),
      'role' => 1
    ];

    $userData = array_merge($defaultData, $data);

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->execute($userData);

    return $db->lastInsertId();
  }

  protected function createTestMessage(array $data = []): int
  {
    global $db;
    $defaultData = [
      'user_id' => $this->createTestUser(),
      'message' => 'Test message content',
      'status' => 1
    ];

    $messageData = array_merge($defaultData, $data);

    $stmt = $db->prepare("INSERT INTO messages (user_id, message, status) VALUES (:user_id, :message, :status)");
    $stmt->execute($messageData);

    return $db->lastInsertId();
  }
}
