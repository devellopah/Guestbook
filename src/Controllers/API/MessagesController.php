<?php

namespace Controllers\API;

use Services\MessageService;
use Exception;

class MessagesController extends BaseApiController
{
  protected MessageService $messageService;

  public function __construct()
  {
    parent::__construct();
    // Get MessageService from the container since we're not using BaseController constructor
    global $container;
    if (isset($container)) {
      $this->messageService = $container->make(\Services\MessageService::class);
    }
  }

  /**
   * GET /api/v1/messages - List messages with pagination
   */
  public function index(): void
  {
    try {
      $this->logRequest('/api/v1/messages', 'GET');

      $pagination = $this->getPaginationParams();
      $onlyActive = !$this->checkAdmin();

      $result = $this->messageService->getMessages(
        $pagination['page'],
        $pagination['per_page'],
        $onlyActive
      );

      $messages = array_map(function ($message) {
        return [
          'id' => $message->getId(),
          'message' => $message->getMessage(),
          'user_id' => $message->getUserId(),
          'status' => $message->getStatus(),
          'created_at' => $message->getCreatedAt(),
          'user' => [
            'id' => $message->getUser()->getId(),
            'name' => $message->getUser()->getName()
          ]
        ];
      }, $result['messages']);

      $response = [
        'messages' => $messages,
        'pagination' => [
          'current_page' => $pagination['page'],
          'per_page' => $pagination['per_page'],
          'total' => $result['total'],
          'total_pages' => ceil($result['total'] / $pagination['per_page']),
          'has_next' => $pagination['page'] < ceil($result['total'] / $pagination['per_page']),
          'has_prev' => $pagination['page'] > 1
        ]
      ];

      $this->successResponse($response);
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 500);
    }
  }

  /**
   * POST /api/v1/messages - Create a new message
   */
  public function create(): void
  {
    try {
      if (!$this->checkAuth()) {
        $this->errorResponse('Authentication required', 401);
        return;
      }

      if (!$this->checkRateLimit('message_post', 3, 60)) {
        $remainingTime = $this->getRateLimitRemainingTime('message_post');
        $this->errorResponse("Too many message submissions. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.", 429);
        return;
      }

      $data = $this->getJsonInput();
      $this->validateRequired($data, ['message']);

      $user = $this->getUser();
      $message = $this->messageService->createMessage($user['id'], $data['message']);

      $responseData = [
        'id' => $message->getId(),
        'message' => $message->getMessage(),
        'user_id' => $message->getUserId(),
        'status' => $message->getStatus(),
        'created_at' => $message->getCreatedAt()
      ];

      $this->successResponse($responseData, 'Message created successfully', 201);
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }

  /**
   * GET /api/v1/messages/{id} - Get single message
   */
  public function show(int $id): void
  {
    try {
      $this->logRequest("/api/v1/messages/{$id}", 'GET');

      $message = $this->messageService->getMessageById($id);

      if (!$message) {
        $this->errorResponse('Message not found', 404);
        return;
      }

      // Check permissions - only show active messages to non-admins
      if (!$this->checkAdmin() && $message->getStatus() !== 'active') {
        $this->errorResponse('Message not found', 404);
        return;
      }

      $responseData = [
        'id' => $message->getId(),
        'message' => $message->getMessage(),
        'user_id' => $message->getUserId(),
        'status' => $message->getStatus(),
        'created_at' => $message->getCreatedAt(),
        'user' => [
          'id' => $message->getUser()->getId(),
          'name' => $message->getUser()->getName()
        ]
      ];

      $this->successResponse($responseData);
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 500);
    }
  }

  /**
   * PUT /api/v1/messages/{id} - Update message
   */
  public function update(int $id): void
  {
    try {
      if (!$this->checkAuth()) {
        $this->errorResponse('Authentication required', 401);
        return;
      }

      $data = $this->getJsonInput();
      $this->validateRequired($data, ['message']);

      $user = $this->getUser();
      $role = \Core\Role::tryFrom($user['role']);
      $isAdmin = $role && $role->canEditAnyMessage();

      $message = $this->messageService->updateMessage(
        $id,
        $data['message'],
        $user['id'],
        $isAdmin
      );

      $responseData = [
        'id' => $message->getId(),
        'message' => $message->getMessage(),
        'user_id' => $message->getUserId(),
        'status' => $message->getStatus(),
        'created_at' => $message->getCreatedAt()
      ];

      $this->successResponse($responseData, 'Message updated successfully');
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }

  /**
   * DELETE /api/v1/messages/{id} - Delete message
   */
  public function delete(int $id): void
  {
    try {
      if (!$this->checkAuth()) {
        $this->errorResponse('Authentication required', 401);
        return;
      }

      $user = $this->getUser();
      $role = \Core\Role::tryFrom($user['role']);
      $isAdmin = $role && $role->canEditAnyMessage();

      $result = $this->messageService->deleteMessage($id, $user['id'], $isAdmin);

      if ($result) {
        $this->successResponse([], 'Message deleted successfully');
      } else {
        $this->errorResponse('Failed to delete message', 500);
      }
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }

  /**
   * PATCH /api/v1/messages/{id}/status - Toggle message status
   */
  public function toggleStatus(int $id): void
  {
    try {
      if (!$this->checkAdmin()) {
        $this->errorResponse('Admin access required', 403);
        return;
      }

      $message = $this->messageService->toggleMessageStatus($id);

      $responseData = [
        'id' => $message->getId(),
        'status' => $message->getStatus()
      ];

      $this->successResponse($responseData, 'Message status updated successfully');
    } catch (Exception $e) {
      $this->errorResponse($e->getMessage(), 400);
    }
  }
}
