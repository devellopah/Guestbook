<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Queue\Job;
use Queue\QueueService;

class QueueTest extends TestCase
{
  public function testJobInterface(): void
  {
    $job = $this->createMock(Job::class);
    $job->method('getName')->willReturn('test_job');

    $this->assertEquals('test_job', $job->getName());
    $this->assertTrue(method_exists($job, 'handle'), 'Job must implement handle()');
  }

  public function testQueuePushAndPop(): void
  {
    $mockJob = new class implements Job {
      public bool $handled = false;

      public function handle(): void
      {
        $this->handled = true;
      }

      public function getName(): string
      {
        return 'test_job';
      }
    };

    // Use a separate Redis DB index for testing (db=1)
    $queue = new QueueService(new \Predis\Client([
      'scheme' => 'tcp',
      'host'   => 'redis',
      'port'   => 6379,
      'db'     => 1,
    ]));

    $queue->clear('test');

    // Push
    $queue->pushOn('test', $mockJob);
    $this->assertEquals(1, $queue->size('test'));

    // Pop
    $popped = $queue->pop('test');
    $this->assertNotNull($popped);
    $this->assertEquals('test_job', $popped->getName());

    // Handle
    $popped->handle();
    $this->assertTrue($mockJob->handled);

    // Empty
    $this->assertEquals(0, $queue->size('test'));
    $this->assertNull($queue->pop('test'));

    $queue->clear('test');
  }

  public function testFailedJobs(): void
  {
    $failingJob = new class implements Job {
      public function handle(): void
      {
        throw new \RuntimeException('Test failure');
      }

      public function getName(): string
      {
        return 'failing_job';
      }
    };

    $queue = new QueueService(new \Predis\Client([
      'scheme' => 'tcp',
      'host'   => 'redis',
      'port'   => 6379,
      'db'     => 1,
    ]));

    $queue->clear('failed_test');

    $queue->pushOn('failed_test', $failingJob);
    $job = $queue->pop('failed_test');

    $this->assertNotNull($job);
    $queue->failed($job, new \RuntimeException('Test failure'));

    $failed = $queue->getFailedJobs();
    $this->assertNotEmpty($failed);

    $queue->clear('failed_test');
  }

  public function testMultipleJobs(): void
  {
    $queue = new QueueService(new \Predis\Client([
      'scheme' => 'tcp',
      'host'   => 'redis',
      'port'   => 6379,
      'db'     => 1,
    ]));

    $queue->clear('multi');

    $count = 5;
    for ($i = 0; $i < $count; $i++) {
      $job = new class($i) implements Job {
        private int $id;

        public function __construct(int $id)
        {
          $this->id = $id;
        }

        public function handle(): void {}
        public function getName(): string
        {
          return "job_{$this->id}";
        }
      };
      $queue->pushOn('multi', $job);
    }

    $this->assertEquals($count, $queue->size('multi'));

    // Pop all
    $popped = 0;
    while ($queue->pop('multi') !== null) {
      $popped++;
    }
    $this->assertEquals($count, $popped);
    $this->assertEquals(0, $queue->size('multi'));
  }
}
