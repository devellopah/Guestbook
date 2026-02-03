<?php

namespace Models;

use Exception;
use PDO;
use PDOException;

class Database
{
  private static ?PDO $instance = null;

  public static function getInstance(): PDO
  {
    if (self::$instance === null) {
      self::connect();
    }
    return self::$instance;
  }

  private static function connect(): void
  {
    try {
      $host = $_ENV['DB_HOST'] ?? 'db';
      $port = $_ENV['DB_PORT'] ?? '3306';
      $dbname = $_ENV['DB_NAME'] ?? 'guestbook';
      $username = $_ENV['DB_USER'] ?? 'root';
      $password = $_ENV['DB_PASS'] ?? 'password';

      $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

      self::$instance = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true,
      ]);

      // Test connection
      self::$instance->query('SELECT 1');
    } catch (PDOException $e) {
      error_log("Database connection failed: " . $e->getMessage());
      throw new Exception("Database service temporarily unavailable");
    }
  }

  public static function query(string $sql, array $params = []): \PDOStatement
  {
    try {
      $stmt = self::getInstance()->prepare($sql);
      $stmt->execute($params);
      return $stmt;
    } catch (PDOException $e) {
      error_log("Database query error: " . $e->getMessage() . " SQL: {$sql}");
      throw new Exception("Database service temporarily unavailable");
    }
  }

  public static function lastInsertId(): string
  {
    return self::getInstance()->lastInsertId();
  }

  public static function beginTransaction(): bool
  {
    return self::getInstance()->beginTransaction();
  }

  public static function commit(): bool
  {
    return self::getInstance()->commit();
  }

  public static function rollBack(): bool
  {
    return self::getInstance()->rollBack();
  }
}
