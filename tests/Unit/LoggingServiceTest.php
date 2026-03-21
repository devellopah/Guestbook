<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\LoggingService;

class LoggingServiceTest extends TestCase
{
  private LoggingService $loggingService;
  private string $testLogDir;
  private string $testLogFile;

  protected function setUp(): void
  {
    parent::setUp();

    // Create a temporary directory for testing
    $this->testLogDir = sys_get_temp_dir() . '/guestbook_logs_test_' . uniqid();
    $this->testLogFile = $this->testLogDir . '/test.log';

    // Create the directory
    mkdir($this->testLogDir, 0755, true);

    // Create LoggingService with test configuration
    $this->loggingService = new LoggingService();

    // Override the log directory and file for testing
    // Use a simpler approach to set test paths
    $this->loggingService->setTestPaths($this->testLogDir, $this->testLogFile);

    // Set log level to DEBUG for testing
    $this->loggingService->setLogLevel('DEBUG');
    $this->loggingService->setEnabled(true);
  }

  protected function tearDown(): void
  {
    parent::tearDown();

    // Clean up test files
    if (file_exists($this->testLogFile)) {
      unlink($this->testLogFile);
    }
    if (file_exists($this->testLogDir)) {
      rmdir($this->testLogDir);
    }
  }

  public function testDebugLogging(): void
  {
    $message = 'This is a debug message';
    $context = ['key' => 'value'];

    $this->loggingService->debug($message, $context);

    $this->assertLogFileContains('DEBUG', $message);
    $this->assertLogFileContains('DEBUG', 'value');
  }

  public function testInfoLogging(): void
  {
    $message = 'This is an info message';
    $context = ['user' => 'test'];

    $this->loggingService->info($message, $context);

    $this->assertLogFileContains('INFO', $message);
    $this->assertLogFileContains('INFO', 'test');
  }

  public function testWarningLogging(): void
  {
    $message = 'This is a warning message';
    $context = ['warning' => 'test'];

    $this->loggingService->warning($message, $context);

    $this->assertLogFileContains('WARNING', $message);
    $this->assertLogFileContains('WARNING', 'test');
  }

  public function testErrorLogging(): void
  {
    $message = 'This is an error message';
    $context = ['error' => 'test'];

    $this->loggingService->error($message, $context);

    $this->assertLogFileContains('ERROR', $message);
    $this->assertLogFileContains('ERROR', 'test');
  }

  public function testCriticalLogging(): void
  {
    $message = 'This is a critical message';
    $context = ['critical' => 'test'];

    $this->loggingService->critical($message, $context);

    $this->assertLogFileContains('CRITICAL', $message);
    $this->assertLogFileContains('CRITICAL', 'test');
  }

  public function testEmergencyLogging(): void
  {
    $message = 'This is an emergency message';
    $context = ['emergency' => 'test'];

    $this->loggingService->emergency($message, $context);

    $this->assertLogFileContains('EMERGENCY', $message);
    $this->assertLogFileContains('EMERGENCY', 'test');
  }

  public function testLoggingDisabled(): void
  {
    $this->loggingService->setEnabled(false);

    $this->loggingService->info('This should not be logged');

    $this->assertFileDoesNotExist($this->testLogFile);
  }

  public function testLogLevelFiltering(): void
  {
    // Set log level to WARNING, so DEBUG and INFO should be filtered out
    $this->loggingService->setLogLevel('WARNING');

    $this->loggingService->debug('Debug message');
    $this->loggingService->info('Info message');
    $this->loggingService->warning('Warning message');

    $logContent = file_get_contents($this->testLogFile);

    $this->assertStringNotContainsString('DEBUG', $logContent);
    $this->assertStringNotContainsString('INFO', $logContent);
    $this->assertStringContainsString('WARNING', $logContent);
  }

  public function testLogQuery(): void
  {
    $query = 'SELECT * FROM users';
    $executionTime = 0.05;
    $params = ['id' => 1];

    $this->loggingService->logQuery($query, $executionTime, $params);

    $this->assertLogFileContains('DEBUG', $query);
    $this->assertLogFileContains('DEBUG', (string)$executionTime);
    $this->assertLogFileContains('DEBUG', 'params');
  }

  public function testLogAuth(): void
  {
    $event = 'login';
    $username = 'testuser';
    $context = ['ip' => '127.0.0.1'];

    $this->loggingService->logAuth($event, $username, $context);

    $this->assertLogFileContains('INFO', $event);
    $this->assertLogFileContains('INFO', $username);
    $this->assertLogFileContains('INFO', '127.0.0.1');
  }

  public function testLogValidation(): void
  {
    $scenario = 'user_registration';
    $errors = ['email' => ['Email is required']];
    $data = ['name' => 'test'];

    $this->loggingService->logValidation($scenario, $errors, $data);

    $this->assertLogFileContains('WARNING', $scenario);
    $this->assertLogFileContains('WARNING', 'errors');
    $this->assertLogFileContains('WARNING', 'data');
  }

  public function testLogService(): void
  {
    $service = 'MessageService';
    $operation = 'createMessage';
    $context = ['userId' => 1];

    $this->loggingService->logService($service, $operation, $context);

    $this->assertLogFileContains('DEBUG', $service);
    $this->assertLogFileContains('DEBUG', $operation);
    $this->assertLogFileContains('DEBUG', '1');
  }

  public function testLogCache(): void
  {
    $operation = 'set';
    $key = 'test_key';
    $context = ['ttl' => 300];

    $this->loggingService->logCache($operation, $key, $context);

    $this->assertLogFileContains('DEBUG', $operation);
    $this->assertLogFileContains('DEBUG', $key);
    $this->assertLogFileContains('DEBUG', '300');
  }

  public function testLogSecurity(): void
  {
    $event = 'failed_login';
    $ip = '192.168.1.1';
    $context = ['attempts' => 3];

    $this->loggingService->logSecurity($event, $ip, $context);

    $this->assertLogFileContains('WARNING', $event);
    $this->assertLogFileContains('WARNING', $ip);
    $this->assertLogFileContains('WARNING', '3');
  }

  public function testGetLogFile(): void
  {
    $this->assertEquals($this->testLogFile, $this->loggingService->getLogFile());
  }

  public function testGetLogDir(): void
  {
    $this->assertEquals($this->testLogDir, $this->loggingService->getLogDir());
  }

  public function testGetLogLevel(): void
  {
    $this->assertEquals('DEBUG', $this->loggingService->getLogLevel());

    $this->loggingService->setLogLevel('ERROR');
    $this->assertEquals('ERROR', $this->loggingService->getLogLevel());
  }

  public function testSetLogLevel(): void
  {
    $result = $this->loggingService->setLogLevel('WARNING');
    $this->assertTrue($result);
    $this->assertEquals('WARNING', $this->loggingService->getLogLevel());

    $result = $this->loggingService->setLogLevel('INVALID');
    $this->assertFalse($result);
  }

  public function testSetEnabled(): void
  {
    $this->loggingService->setEnabled(false);
    $this->loggingService->info('Test message');
    $this->assertFileDoesNotExist($this->testLogFile);

    $this->loggingService->setEnabled(true);
    $this->loggingService->info('Test message');
    $this->assertFileExists($this->testLogFile);
  }

  public function testGetStats(): void
  {
    // Log some messages
    $this->loggingService->info('Test info message');
    $this->loggingService->error('Test error message');
    $this->loggingService->warning('Test warning message');

    // Force flush to ensure messages are written
    $this->loggingService->info('Flush message');

    $stats = $this->loggingService->getStats();

    $this->assertArrayHasKey('total_lines', $stats);
    $this->assertArrayHasKey('file_size', $stats);
    $this->assertArrayHasKey('last_modified', $stats);
    $this->assertArrayHasKey('log_levels', $stats);

    $this->assertGreaterThan(0, $stats['total_lines']);
    $this->assertGreaterThan(0, $stats['file_size']);
    $this->assertNotEmpty($stats['last_modified']);
    $this->assertArrayHasKey('INFO', $stats['log_levels']);
    $this->assertArrayHasKey('ERROR', $stats['log_levels']);
    $this->assertArrayHasKey('WARNING', $stats['log_levels']);
  }

  public function testClear(): void
  {
    // Log a message
    $this->loggingService->info('Test message');
    $this->assertFileExists($this->testLogFile);

    // Clear the log
    $result = $this->loggingService->clear();
    $this->assertTrue($result);

    // Check if file is empty
    $this->assertFileExists($this->testLogFile);
    $this->assertEquals(0, filesize($this->testLogFile));
  }

  public function testRotate(): void
  {
    // Log a message
    $this->loggingService->info('Test message');
    $this->assertFileExists($this->testLogFile);

    // Rotate the log
    $result = $this->loggingService->rotate();
    $this->assertTrue($result);

    // Check if rotated file exists and original is cleared
    $rotatedFiles = glob($this->testLogFile . '.*');
    $this->assertCount(1, $rotatedFiles);
    $this->assertFileExists($rotatedFiles[0]);
    $this->assertEquals(0, filesize($this->testLogFile));
  }

  public function testRotateWhenNoLogFile(): void
  {
    // Remove the log file
    if (file_exists($this->testLogFile)) {
      unlink($this->testLogFile);
    }

    // Rotate should still return true
    $result = $this->loggingService->rotate();
    $this->assertTrue($result);
  }

  /**
   * Helper method to check if log file contains specific content
   */
  private function assertLogFileContains(string $level, string $content): void
  {
    $this->assertFileExists($this->testLogFile);
    $logContent = file_get_contents($this->testLogFile);

    $this->assertStringContainsString("{$level}:", $logContent);
    $this->assertStringContainsString($content, $logContent);
  }
}
