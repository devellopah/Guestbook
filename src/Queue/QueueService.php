<?php

namespace Queue;

use Predis\Client as Redis;

class QueueService
{
  private Redis $redis;
  private string $prefix = 'queue:';

  public function __construct(?Redis $redis = null)
  {
    $this->redis = $redis ?? new Redis([
      'scheme' => 'tcp',
      'host'   => 'redis',
      'port'   => 6379,
    ]);
  }

  public function push(Job $job): void
  {
    $payload = serialize($job);
    $this->redis->rpush($this->prefix . 'default', [$payload]);
  }

  public function pushOn(string $queue, Job $job): void
  {
    $payload = serialize($job);
    $this->redis->rpush($this->prefix . $queue, [$payload]);
  }

  public function pop(string $queue = 'default'): ?Job
  {
    $payload = $this->redis->lpop($this->prefix . $queue);

    if ($payload === null) {
      return null;
    }

    $job = unserialize($payload);

    if (!$job instanceof Job) {
      return null;
    }

    return $job;
  }

  public function size(string $queue = 'default'): int
  {
    return $this->redis->llen($this->prefix . $queue);
  }

  public function clear(string $queue = 'default'): void
  {
    $this->redis->del([$this->prefix . $queue]);
  }

  public function getQueues(): array
  {
    $keys = $this->redis->keys($this->prefix . '*');
    return array_map(function ($key) {
      return str_replace($this->prefix, '', $key);
    }, $keys);
  }

  public function failed(Job $job, \Throwable $e): void
  {
    $failed = [
      'job' => serialize($job),
      'exception' => [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ],
      'failed_at' => date('Y-m-d H:i:s'),
    ];

    $this->redis->rpush($this->prefix . '_failed', [serialize($failed)]);
  }

  public function getFailedJobs(): array
  {
    $payloads = $this->redis->lrange($this->prefix . '_failed', 0, -1);
    return array_map(function ($payload) {
      return unserialize($payload);
    }, $payloads);
  }
}
