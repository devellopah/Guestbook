<?php

namespace Services;

use Exception;

class LoggingService extends BaseService
{
  private string $logDir;
  private string $logFile;
  private bool $enabled;
  private array $logLevels = [
    'DEBUG' => 100,
    'INFO' => 200,
    'NOTICE' => 250,
    'WARNING' => 300,
    'ERROR' => 400,
    'CRITICAL' => 500,
    'ALERT' => 550,
    'EMERGENCY' => 600
  ];
  private int $minLevel;

  public function __construct()
  {
    parent::__construct();
    $this->setupLogging();
  }

  /**
   * Setup logging configuration
   */
  private function setupLogging(): void
  {
    $this->enabled = $this->getEnv('LOG_ENABLED', true);
    $this->minLevel = $this->getEnv('LOG_MIN_LEVEL', $this->logLevels['INFO']);
    $this->logDir = $this->getEnv('LOG_DIR', sys_get_temp_dir() . '/guestbook_logs');
    $this->logFile = $this->logDir . '/guestbook.log';

    // Create log directory if it doesn't exist
    if (!file_exists($this->logDir)) {
      mkdir($this->logDir, 0755, true);
    }
  }

  /**
   * Get environment variable with fallback
   */
  private function getEnv(string $key, mixed $default = null): mixed
  {
    $value = getenv($key);
    return $value !== false ? $value : $default;
  }

  /**
   * Set test paths for testing purposes
   */
  public function setTestPaths(string $logDir, string $logFile): void
  {
    $this->logDir = $logDir;
    $this->logFile = $logFile;
  }

  /**
   * Log debug message
   */
  public function debug(string $message, array $context = []): void
  {
    $this->writeLog('DEBUG', $message, $context);
  }

  /**
   * Log info message
   */
  public function info(string $message, array $context = []): void
  {
    $this->writeLog('INFO', $message, $context);
  }

  /**
   * Log notice message
   */
  public function notice(string $message, array $context = []): void
  {
    $this->writeLog('NOTICE', $message, $context);
  }

  /**
   * Log warning message
   */
  public function warning(string $message, array $context = []): void
  {
    $this->writeLog('WARNING', $message, $context);
  }

  /**
   * Log error message
   */
  public function error(string $message, array $context = []): void
  {
    $this->writeLog('ERROR', $message, $context);
  }

  /**
   * Log critical message
   */
  public function critical(string $message, array $context = []): void
  {
    $this->writeLog('CRITICAL', $message, $context);
  }

  /**
   * Log alert message
   */
  public function alert(string $message, array $context = []): void
  {
    $this->writeLog('ALERT', $message, $context);
  }

  /**
   * Log emergency message
   */
  public function emergency(string $message, array $context = []): void
  {
    $this->writeLog('EMERGENCY', $message, $context);
  }

  /**
   * Generic log method
   */
  public function writeLog(string $level, string $message, array $context = []): void
  {
    // Check if logging is enabled and level is sufficient
    if (!$this->enabled || !isset($this->logLevels[$level]) || $this->logLevels[$level] < $this->minLevel) {
      return;
    }

    // Format the log entry
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = sprintf('[%s] %s: %s%s' . PHP_EOL, $timestamp, $level, $message, $contextStr);

    // Write to file
    try {
      file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
      // Fallback to error_log if file writing fails
      error_log("LoggingService failed to write log: " . $e->getMessage());
    }

    // Also log to PHP error log for critical and emergency levels
    if ($level === 'CRITICAL' || $level === 'EMERGENCY') {
      error_log($logEntry);
    }
  }

  /**
   * Log database query
   */
  public function logQuery(string $query, float $executionTime, array $params = []): void
  {
    $this->debug('Database Query', [
      'query' => $query,
      'execution_time' => $executionTime,
      'params' => $params
    ]);
  }

  /**
   * Log authentication events
   */
  public function logAuth(string $event, string $username, array $context = []): void
  {
    $this->info('Authentication Event', array_merge([
      'event' => $event,
      'username' => $username
    ], $context));
  }

  /**
   * Log validation errors
   */
  public function logValidation(string $scenario, array $errors, array $data = []): void
  {
    $this->warning('Validation Failed', [
      'scenario' => $scenario,
      'errors' => $errors,
      'data' => $data
    ]);
  }

  /**
   * Log service operations
   */
  public function logService(string $service, string $operation, array $context = []): void
  {
    $this->debug('Service Operation', array_merge([
      'service' => $service,
      'operation' => $operation
    ], $context));
  }

  /**
   * Log cache operations
   */
  public function logCache(string $operation, string $key, array $context = []): void
  {
    $this->debug('Cache Operation', array_merge([
      'operation' => $operation,
      'key' => $key
    ], $context));
  }

  /**
   * Log security events
   */
  public function logSecurity(string $event, string $ip, array $context = []): void
  {
    $this->warning('Security Event', array_merge([
      'event' => $event,
      'ip' => $ip
    ], $context));
  }

  /**
   * Log message operations (database-based)
   */
  public function logMessage(int $messageId, string $action, string $userId, array $context = []): bool
  {
    try {
      $query = "INSERT INTO message_logs (message_id, action, user_id, context, created_at) 
                VALUES (:message_id, :action, :user_id, :context, NOW())";

      $contextJson = json_encode($context);

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':message_id', $messageId, \PDO::PARAM_INT);
      $stmt->bindParam(':action', $action);
      $stmt->bindParam(':user_id', $userId, \PDO::PARAM_STR);
      $stmt->bindParam(':context', $contextJson);

      return $stmt->execute();
    } catch (Exception $e) {
      $this->error('Failed to log message operation', [
        'message_id' => $messageId,
        'action' => $action,
        'user_id' => $userId,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Log user actions (database-based)
   */
  public function logUserAction(string $userId, string $action, string $resource, array $context = []): bool
  {
    try {
      $query = "INSERT INTO user_logs (user_id, action, resource, context, created_at) 
                VALUES (:user_id, :action, :resource, :context, NOW())";

      $contextJson = json_encode($context);

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':user_id', $userId, \PDO::PARAM_STR);
      $stmt->bindParam(':action', $action);
      $stmt->bindParam(':resource', $resource);
      $stmt->bindParam(':context', $contextJson);

      return $stmt->execute();
    } catch (Exception $e) {
      $this->error('Failed to log user action', [
        'user_id' => $userId,
        'action' => $action,
        'resource' => $resource,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Log errors (database-based)
   */
  public function logError(string $level, string $message, string $context = '', ?string $userId = null): bool
  {
    try {
      $query = "INSERT INTO error_logs (level, message, context, user_id, created_at) 
                VALUES (:level, :message, :context, :user_id, NOW())";

      $stmt = $this->db->prepare($query);
      $stmt->bindParam(':level', $level);
      $stmt->bindParam(':message', $message);
      $stmt->bindParam(':context', $context);
      $stmt->bindParam(':user_id', $userId, \PDO::PARAM_STR);

      return $stmt->execute();
    } catch (Exception $e) {
      // If database logging fails, fall back to file logging
      $this->error('Failed to log error to database', [
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'user_id' => $userId,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  /**
   * Get log file path
   */
  public function getLogFile(): string
  {
    return $this->logFile;
  }

  /**
   * Get log directory
   */
  public function getLogDir(): string
  {
    return $this->logDir;
  }

  /**
   * Get current log level
   */
  public function getLogLevel(): string
  {
    foreach ($this->logLevels as $level => $value) {
      if ($value === $this->minLevel) {
        return $level;
      }
    }
    return 'INFO';
  }

  /**
   * Set log level
   */
  public function setLogLevel(string $level): bool
  {
    if (!isset($this->logLevels[$level])) {
      return false;
    }
    $this->minLevel = $this->logLevels[$level];
    return true;
  }

  /**
   * Enable or disable logging
   */
  public function setEnabled(bool $enabled): void
  {
    $this->enabled = $enabled;
  }

  /**
   * Get log statistics
   */
  public function getStats(): array
  {
    if (!file_exists($this->logFile)) {
      return [
        'total_lines' => 0,
        'file_size' => 0,
        'last_modified' => null,
        'log_levels' => []
      ];
    }

    $stats = stat($this->logFile);
    $fileSize = $stats['size'];
    $lastModified = date('Y-m-d H:i:s', $stats['mtime']);

    // Count log levels (simple implementation)
    $logLevels = [];
    $handle = fopen($this->logFile, 'r');
    if ($handle) {
      while (($line = fgets($handle)) !== false) {
        foreach ($this->logLevels as $level => $value) {
          if (strpos($line, "$level:") !== false) {
            $logLevels[$level] = ($logLevels[$level] ?? 0) + 1;
            break;
          }
        }
      }
      fclose($handle);
    }

    return [
      'total_lines' => array_sum($logLevels),
      'file_size' => $fileSize,
      'last_modified' => $lastModified,
      'log_levels' => $logLevels
    ];
  }

  /**
   * Clear log file
   */
  public function clear(): bool
  {
    try {
      if (file_exists($this->logFile)) {
        file_put_contents($this->logFile, '');
      }
      return true;
    } catch (Exception $e) {
      $this->error('Failed to clear log file', ['error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Rotate log file
   */
  public function rotate(): bool
  {
    try {
      if (!file_exists($this->logFile)) {
        return true;
      }

      $timestamp = date('Y-m-d_H-i-s');
      $rotatedFile = $this->logFile . '.' . $timestamp;

      // Copy current log to rotated file
      copy($this->logFile, $rotatedFile);

      // Clear current log file
      $this->clear();

      return true;
    } catch (Exception $e) {
      $this->error('Failed to rotate log file', ['error' => $e->getMessage()]);
      return false;
    }
  }
}
