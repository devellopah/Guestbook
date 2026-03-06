<?php

namespace Controllers;

use Core\BaseController;
use Models\Message;
use Models\Pagination;
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
      $message = new Message([
        'user_id' => $this->getUser()['id'],
        'message' => $data['message']
      ]);
      $message->save();

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
      $message = Message::findById((int) $data['id']);

      if (!$message) {
        $this->flash('error', 'Message not found');
        $this->redirect("/?page={$data['page']}");
        return;
      }

      // Check if user can edit this message (owner or admin)
      $user = $this->getUser();
      $role = \Core\Role::tryFrom($user['role']);
      if ($user['id'] !== $message->getUserId() && $role && !$role->canEditAnyMessage()) {
        $this->flash('error', 'You can only edit your own messages');
        $this->redirect("/?page={$data['page']}");
        return;
      }

      $message->setMessage($data['message']);
      $message->save();

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
    $status = isset($_GET['status']) ? (int) $_GET['status'] : 0;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    try {
      $message = Message::findById($id);

      if ($message) {
        $message->toggleStatus();
      }
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
    $total = Message::getCount(!$this->checkAdmin());
    $pagination = new Pagination($page, $perPage, $total);
    $start = $pagination->getStart();

    // Get messages
    $messages = Message::getAll($perPage, $start, !$this->checkAdmin());

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
