<?php

namespace Services;

use Models\Message;
use Models\Pagination;
use Exception;

class MessageService
{
  public function getMessages(int $page = 1, int $perPage = 4, bool $onlyActive = true): array
  {
    $total = Message::getCount($onlyActive);
    $pagination = new Pagination($page, $perPage, $total);
    $start = $pagination->getStart();

    $messages = Message::getAll($perPage, $start, $onlyActive);

    return [
      'messages' => $messages,
      'pagination' => $pagination,
      'total' => $total
    ];
  }

  public function createMessage(int $userId, string $messageText): Message
  {
    $message = new Message([
      'user_id' => $userId,
      'message' => $messageText
    ]);
    $message->save();
    return $message;
  }

  public function updateMessage(int $messageId, string $messageText, int $userId, bool $isAdmin = false): Message
  {
    $message = Message::findById($messageId);

    if (!$message) {
      throw new Exception('Message not found');
    }

    // Check permissions
    if (!$isAdmin && $userId !== $message->getUserId()) {
      throw new Exception('You can only edit your own messages');
    }

    $message->setMessage($messageText);
    $message->save();
    return $message;
  }

  public function toggleMessageStatus(int $messageId): Message
  {
    $message = Message::findById($messageId);

    if (!$message) {
      throw new Exception('Message not found');
    }

    $message->toggleStatus();
    return $message;
  }

  public function getMessageById(int $messageId): ?Message
  {
    return Message::findById($messageId);
  }
}
