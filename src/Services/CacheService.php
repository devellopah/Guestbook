<?php

namespace Services;

use Exception;

class CacheService extends BaseService
{
  private string $cacheDir;
  private int $defaultTtl;

  public function __construct()
  {
    parent::__construct();
    $this->cacheDir = sys_get_temp_dir() . '/guestbook_cache';
    $this->defaultTtl = 300; // 5 minutes default TTL

    // Ensure cache directory exists
    if (!file_exists($this->cacheDir)) {
      mkdir($this->cacheDir, 0755, true);
    }
  }

  /**
   * Generate cache key
   */
  private function getCacheKey(string $key): string
  {
    return md5($key);
  }

  /**
   * Store data in cache
   */
  public function set(string $key, mixed $data, ?int $ttl = null): bool
  {
    if ($ttl === null) {
      $ttl = $this->defaultTtl;
    }

    $cacheKey = $this->getCacheKey($key);
    $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';

    $cacheData = [
      'data' => $data,
      'expires' => time() + $ttl,
      'created' => time()
    ];

    try {
      $result = file_put_contents($cacheFile, serialize($cacheData));
      $this->log('Cache set', ['key' => $key, 'ttl' => $ttl, 'success' => $result !== false]);
      return $result !== false;
    } catch (Exception $e) {
      $this->log('Cache set failed', ['key' => $key, 'error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Retrieve data from cache
   */
  public function get(string $key): mixed
  {
    $cacheKey = $this->getCacheKey($key);
    $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';

    if (!file_exists($cacheFile)) {
      $this->log('Cache miss', ['key' => $key]);
      return null;
    }

    try {
      $cacheData = unserialize(file_get_contents($cacheFile));

      if ($cacheData === false) {
        $this->log('Cache corrupted', ['key' => $key]);
        return null;
      }

      if (time() > $cacheData['expires']) {
        $this->log('Cache expired', ['key' => $key]);
        $this->delete($key);
        return null;
      }

      $this->log('Cache hit', ['key' => $key]);
      return $cacheData['data'];
    } catch (Exception $e) {
      $this->log('Cache get failed', ['key' => $key, 'error' => $e->getMessage()]);
      return null;
    }
  }

  /**
   * Check if cache exists and is valid
   */
  public function has(string $key): bool
  {
    return $this->get($key) !== null;
  }

  /**
   * Delete cache entry
   */
  public function delete(string $key): bool
  {
    $cacheKey = $this->getCacheKey($key);
    $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';

    if (file_exists($cacheFile)) {
      try {
        $result = unlink($cacheFile);
        $this->log('Cache deleted', ['key' => $key, 'success' => $result]);
        return $result;
      } catch (Exception $e) {
        $this->log('Cache delete failed', ['key' => $key, 'error' => $e->getMessage()]);
        return false;
      }
    }

    return true; // Already deleted
  }

  /**
   * Clear all cache
   */
  public function clear(): bool
  {
    try {
      $files = glob($this->cacheDir . '/*.cache');
      $success = true;

      foreach ($files as $file) {
        if (is_file($file)) {
          if (!unlink($file)) {
            $success = false;
          }
        }
      }

      $this->log('Cache cleared', ['success' => $success]);
      return $success;
    } catch (Exception $e) {
      $this->log('Cache clear failed', ['error' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Get cache statistics
   */
  public function getStats(): array
  {
    $files = glob($this->cacheDir . '/*.cache');
    $totalSize = 0;
    $validCount = 0;
    $expiredCount = 0;

    foreach ($files as $file) {
      if (is_file($file)) {
        $totalSize += filesize($file);
        $cacheData = unserialize(file_get_contents($file));

        if ($cacheData !== false) {
          if (time() > $cacheData['expires']) {
            $expiredCount++;
          } else {
            $validCount++;
          }
        }
      }
    }

    return [
      'total_files' => count($files),
      'valid_files' => $validCount,
      'expired_files' => $expiredCount,
      'total_size' => $totalSize,
      'cache_dir' => $this->cacheDir
    ];
  }
}
