<?php

namespace Services\Interfaces;

use Models\Message;
use Models\Pagination;

interface MessageServiceInterface
{
  /**
   * Получить сообщения с пагинацией
   */
  public function getMessages(int $page = 1, int $perPage = 4, bool $onlyActive = true): array;

  /**
   * Создать новое сообщение
   */
  public function createMessage(int $userId, string $messageText): Message;

  /**
   * Обновить сообщение
   */
  public function updateMessage(int $messageId, string $messageText, int $userId, bool $isAdmin = false): Message;

  /**
   * Переключить статус сообщения (активно/неактивно)
   */
  public function toggleMessageStatus(int $messageId): Message;

  /**
   * Получить сообщение по ID
   */
  public function getMessageById(int $messageId): ?Message;

  /**
   * Удалить сообщение
   */
  public function deleteMessage(int $messageId, int $userId, bool $isAdmin = false): bool;
}
