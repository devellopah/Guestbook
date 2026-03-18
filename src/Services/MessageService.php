<?php

namespace Services;

use Models\Message;
use Models\Pagination;
use Exception;
use Services\Interfaces\MessageServiceInterface;

class MessageService extends BaseService implements MessageServiceInterface
{
  private ?\Services\CacheService $cacheService = null;

  public function __construct()
  {
    parent::__construct();
    // Try to get cache service from container if available
    try {
      $app = \Core\Application::getInstance();
      $this->cacheService = $app->getContainer()->make(\Services\CacheService::class);
    } catch (\Exception $e) {
      // Cache service not available, continue without caching
      $this->log('Cache service not available', ['error' => $e->getMessage()]);
    }
  }

  public function getMessages(int $page = 1, int $perPage = 4, bool $onlyActive = true): array
  {
    // Generate cache key based on parameters
    $cacheKey = "messages_page_{$page}_perpage_{$perPage}_active_" . ($onlyActive ? '1' : '0');

    // Try to get from cache first
    if ($this->cacheService && $cached = $this->cacheService->get($cacheKey)) {
      $this->log('Messages retrieved from cache', ['key' => $cacheKey]);
      return $cached;
    }

    // Cache miss, fetch from database
    $total = Message::getCount($onlyActive);
    $pagination = new Pagination($page, $perPage, $total);
    $start = $pagination->getStart();

    $messages = Message::getAll($perPage, $start, $onlyActive);

    $result = [
      'messages' => $messages,
      'pagination' => $pagination,
      'total' => $total
    ];

    // Store in cache if cache service is available
    if ($this->cacheService) {
      $this->cacheService->set($cacheKey, $result, 300); // Cache for 5 minutes
      $this->log('Messages cached', ['key' => $cacheKey]);
    }

    return $result;
  }

  public function createMessage(int $userId, string $messageText): Message
  {
    $message = new Message([
      'user_id' => $userId,
      'message' => $messageText
    ]);
    $message->save();

    // Invalidate cache after creation
    if ($this->cacheService) {
      $this->invalidateMessageCache();
      $this->log('Message cache invalidated after creation', ['user_id' => $userId]);
    }

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

    // Invalidate cache after update
    if ($this->cacheService) {
      $this->invalidateMessageCache();
      $this->log('Message cache invalidated after update', ['message_id' => $messageId]);
    }

    return $message;
  }

  public function toggleMessageStatus(int $messageId): Message
  {
    $message = Message::findById($messageId);

    if (!$message) {
      throw new Exception('Message not found');
    }

    $message->toggleStatus();

    // Invalidate cache after status change
    if ($this->cacheService) {
      $this->invalidateMessageCache();
      $this->log('Message cache invalidated after status toggle', ['message_id' => $messageId]);
    }

    return $message;
  }

  public function getMessageById(int $messageId): ?Message
  {
    return Message::findById($messageId);
  }

  public function deleteMessage(int $messageId, int $userId, bool $isAdmin = false): bool
  {
    $message = Message::findById($messageId);

    if (!$message) {
      throw new Exception('Message not found');
    }

    // Check permissions
    if (!$isAdmin && $userId !== $message->getUserId()) {
      throw new Exception('You can only delete your own messages');
    }

    $result = $message->delete();

    // Invalidate cache after deletion
    if ($this->cacheService) {
      $this->invalidateMessageCache();
      $this->log('Message cache invalidated after deletion', ['message_id' => $messageId]);
    }

    return $result;
  }

  /**
   * Invalidate message cache when data changes
   */
  private function invalidateMessageCache(): void
  {
    if ($this->cacheService) {
      // Clear all message-related cache entries
      $this->cacheService->clear();
      $this->log('All message cache cleared');
    }
  }
}
