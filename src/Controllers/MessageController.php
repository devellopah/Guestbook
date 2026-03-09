<?php

namespace Controllers;

use Core\BaseController;
use Exception;

class MessageController extends BaseController
{
  public function index(): void
  {
    $this->handleMessagePosting();
    $this->handleMessageEditing();
    $this->handleStatusToggle();

    $data = $this->getMessagesData();
    $this->render('messages/index', $data);
  }

  private function handleMessagePosting(): void
  {
    if (!$this->isPost() || !isset($_POST['send-message'])) {
      return;
    }

    if (!$this->checkAuth()) {
      $this->flash('error', 'Login is required');
      $this->redirect('/login');
      return;
    }

    if (!$this->checkRateLimit('message_post', 3, 60)) {
      $remainingTime = $this->getRateLimitRemainingTime('message_post');
      $this->flash('error', "Too many message submissions. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.");
      $this->redirect('/');
      return;
    }

    if (!$this->validateCsrfToken()) {
      $this->flash('error', 'Security validation failed');
      $this->redirect('/');
      return;
    }

    $data = $this->getPostData(['message']);

    if (empty($data['message'])) {
      $this->flash('error', 'Message is required');
      $this->redirect('/');
      return;
    }

    try {
      $user = $this->getUser();
      $message = $this->messageService->createMessage($user['id'], $data['message']);

      $this->flash('success', 'Message added');
      $this->redirect('/');
    } catch (Exception $e) {
      $this->flash('error', $e->getMessage());
      $this->redirect('/');
    }
  }

  private function handleMessageEditing(): void
  {
    if (!$this->isPost() || !isset($_POST['edit-message'])) {
      return;
    }

    if (!$this->checkAuth()) {
      $this->flash('error', 'Login is required');
      $this->redirect('/login');
      return;
    }

    if (!$this->validateCsrfToken()) {
      $this->flash('error', 'Security validation failed');
      $this->redirect('/');
      return;
    }

    $data = $this->getPostData(['message', 'id', 'page']);

    try {
      $user = $this->getUser();
      $role = \Core\Role::tryFrom($user['role']);
      $isAdmin = $role && $role->canEditAnyMessage();

      $message = $this->messageService->updateMessage(
        (int) $data['id'],
        $data['message'],
        $user['id'],
        $isAdmin
      );

      $this->flash('success', 'Message was saved');
      $this->redirect("/?page={$data['page']}#message-{$data['id']}");
    } catch (Exception $e) {
      $this->flash('error', $e->getMessage());
      $this->redirect("/?page={$data['page']}");
    }
  }

  private function handleStatusToggle(): void
  {
    if (!isset($_GET['do']) || $_GET['do'] !== 'toggle-status') {
      return;
    }

    if (!$this->checkAdmin()) {
      $this->flash('error', 'Forbidden');
      $this->redirect('/');
      return;
    }

    $id = (int) ($_GET['id'] ?? 0);
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    try {
      $message = $this->messageService->toggleMessageStatus($id);
    } catch (Exception $e) {
      error_log("Toggle status error: " . $e->getMessage());
    }

    $this->redirect("/?page={$page}#message-{$id}");
  }

  private function getMessagesData(): array
  {
    $title = 'Home';
    $flash = $this->getFlash();

    // Get pagination data
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 4;
    $onlyActive = !$this->checkAdmin();

    // Use MessageService to get messages
    $result = $this->messageService->getMessages($page, $perPage, $onlyActive);
    $messages = $result['messages'];
    $pagination = $result['pagination'];

    // Get user data if authenticated
    $user = $this->checkAuth() ? $this->getUser() : null;

    return compact(
      'title',
      'flash',
      'messages',
      'pagination',
      'user'
    );
  }
}
