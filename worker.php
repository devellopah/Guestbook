<?php

/**
 * Queue Worker - processes queued jobs
 *
 * Usage: php worker.php [queue_name] [--sleep=2]
 */

require __DIR__ . '/vendor/autoload.php';

use Queue\QueueService;

$queue = $argv[1] ?? 'default';
$sleep = 2;

// Parse --sleep option
foreach ($argv as $arg) {
  if (str_starts_with($arg, '--sleep=')) {
    $sleep = (int) substr($arg, 8);
  }
}

echo "Worker started. Listening on queue: {$queue}\n";
echo "Press Ctrl+C to stop.\n\n";

$queueService = new QueueService();

while (true) {
  try {
    $job = $queueService->pop($queue);

    if ($job === null) {
      sleep($sleep);
      continue;
    }

    echo '[' . date('H:i:s') . "] Processing: {$job->getName()}\n";

    try {
      $job->handle();
      echo '[' . date('H:i:s') . "] Completed: {$job->getName()}\n";
    } catch (\Throwable $e) {
      echo '[' . date('H:i:s') . "] Failed: {$job->getName()} - {$e->getMessage()}\n";
      $queueService->failed($job, $e);
    }
  } catch (\Throwable $e) {
    echo '[' . date('H:i:s') . "] Worker error: {$e->getMessage()}\n";
    sleep(5);
  }
}
