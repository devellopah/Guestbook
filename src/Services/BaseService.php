<?php

namespace Services;

use Exception;
use Models\Database;

abstract class BaseService
{
  protected Database $db;

  public function __construct()
  {
    $this->db = new Database();
  }

  /**
   * Начать транзакцию
   */
  protected function beginTransaction(): void
  {
    $this->db->beginTransaction();
  }

  /**
   * Зафиксировать транзакцию
   */
  protected function commit(): void
  {
    $this->db->commit();
  }

  /**
   * Откатить транзакцию
   */
  protected function rollback(): void
  {
    $this->db->rollback();
  }

  /**
   * Выполнить запрос с транзакцией
   */
  protected function executeWithTransaction(callable $callback): mixed
  {
    try {
      $this->beginTransaction();
      $result = $callback();
      $this->commit();
      return $result;
    } catch (Exception $e) {
      $this->rollback();
      throw $e;
    }
  }

  /**
   * Логирование операций
   */
  protected function log(string $message, array $context = []): void
  {
    $logMessage = sprintf('[%s] %s', static::class, $message);
    if (!empty($context)) {
      $logMessage .= ' ' . json_encode($context);
    }
    error_log($logMessage);
  }
}
