<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\CacheService;
use Core\Application;

class CacheServiceTest extends TestCase
{
  private CacheService $cacheService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->cacheService = new CacheService();
  }

  protected function tearDown(): void
  {
    // Clear cache after each test
    $this->cacheService->clear();
    parent::tearDown();
  }

  public function testCacheSetAndGet(): void
  {
    $key = 'test_key';
    $data = ['message' => 'Hello World', 'number' => 42];

    // Set cache
    $result = $this->cacheService->set($key, $data);
    $this->assertTrue($result);

    // Get cache
    $cachedData = $this->cacheService->get($key);
    $this->assertEquals($data, $cachedData);
  }

  public function testCacheHas(): void
  {
    $key = 'test_has_key';
    $data = 'test data';

    // Initially cache doesn't exist
    $this->assertFalse($this->cacheService->has($key));

    // Set cache
    $this->cacheService->set($key, $data);

    // Now cache exists
    $this->assertTrue($this->cacheService->has($key));
  }

  public function testCacheDelete(): void
  {
    $key = 'test_delete_key';
    $data = 'test data';

    // Set cache
    $this->cacheService->set($key, $data);

    // Verify it exists
    $this->assertTrue($this->cacheService->has($key));

    // Delete cache
    $result = $this->cacheService->delete($key);
    $this->assertTrue($result);

    // Verify it's gone
    $this->assertFalse($this->cacheService->has($key));
  }

  public function testCacheExpiration(): void
  {
    $key = 'test_expiration_key';
    $data = 'test data';

    // Set cache with very short TTL (1 second)
    $this->cacheService->set($key, $data, 1);

    // Should exist immediately
    $this->assertTrue($this->cacheService->has($key));

    // Wait for expiration
    sleep(2);

    // Should be expired and automatically deleted
    $this->assertFalse($this->cacheService->has($key));
  }

  public function testCacheClear(): void
  {
    $key1 = 'test_clear_key1';
    $key2 = 'test_clear_key2';
    $data1 = 'data1';
    $data2 = 'data2';

    // Set multiple cache entries
    $this->cacheService->set($key1, $data1);
    $this->cacheService->set($key2, $data2);

    // Verify they exist
    $this->assertTrue($this->cacheService->has($key1));
    $this->assertTrue($this->cacheService->has($key2));

    // Clear all cache
    $result = $this->cacheService->clear();
    $this->assertTrue($result);

    // Verify they're gone
    $this->assertFalse($this->cacheService->has($key1));
    $this->assertFalse($this->cacheService->has($key2));
  }

  public function testCacheStats(): void
  {
    $stats = $this->cacheService->getStats();

    $this->assertArrayHasKey('total_files', $stats);
    $this->assertArrayHasKey('valid_files', $stats);
    $this->assertArrayHasKey('expired_files', $stats);
    $this->assertArrayHasKey('total_size', $stats);
    $this->assertArrayHasKey('cache_dir', $stats);

    $this->assertIsInt($stats['total_files']);
    $this->assertIsInt($stats['valid_files']);
    $this->assertIsInt($stats['expired_files']);
    $this->assertIsInt($stats['total_size']);
    $this->assertIsString($stats['cache_dir']);
  }

  public function testCacheWithComplexData(): void
  {
    $key = 'complex_data_key';
    $complexData = [
      'user' => [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com'
      ],
      'messages' => [
        ['id' => 1, 'text' => 'Hello'],
        ['id' => 2, 'text' => 'World']
      ],
      'settings' => [
        'theme' => 'dark',
        'notifications' => true
      ]
    ];

    // Set complex data
    $result = $this->cacheService->set($key, $complexData);
    $this->assertTrue($result);

    // Get complex data
    $cachedData = $this->cacheService->get($key);
    $this->assertEquals($complexData, $cachedData);
  }
}
