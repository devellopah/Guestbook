<?php

namespace Queue\Jobs;

use Queue\Job;
use Models\Message;

class NewMessageNotificationJob implements Job
{
  private int $messageId;
  private string $authorName;
  private string $messagePreview;

  public function __construct(int $messageId, string $authorName, string $messagePreview)
  {
    $this->messageId = $messageId;
    $this->authorName = $authorName;
    $this->messagePreview = $messagePreview;
  }

  public function getName(): string
  {
    return 'new_message_notification';
  }

  public function handle(): void
  {
    // Simulate sending notifications (log + future email)
    $log = sprintf(
      "[Notification] New message #%d from '%s': %s\n",
      $this->messageId,
      $this->authorName,
      substr($this->messagePreview, 0, 50)
    );

    file_put_contents(
      __DIR__ . '/../../logs/notifications.log',
      $log,
      FILE_APPEND | LOCK_EX
    );

    echo $log;
  }
}
