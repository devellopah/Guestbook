<?php

namespace Controllers;

use Core\BaseController;
use Models\User;
use Exception;

class UserController extends BaseController
{
  public function login(): void
  {
    if ($this->checkAuth()) {
      $this->redirect('index.php');
    }

    $title = 'Login';
    $flash = $this->getFlash();

    if ($this->isPost()) {
      if (!$this->checkRateLimit('login_attempt', 5, 300)) {
        $remainingTime = $this->getRateLimitRemainingTime('login_attempt');
        $this->flash('error', "Too many login attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.");
        $this->render('auth/login', compact('title', 'flash'));
        return;
      }

      if (!$this->validateCsrfToken()) {
        $this->flash('error', 'Security validation failed');
        $this->render('auth/login', compact('title', 'flash'));
        return;
      }

      $data = $this->getPostData(['email', 'password']);

      try {
        $user = User::findByEmail($data['email']);

        if ($user && $user->authenticate($data['password'])) {
          // Set session data
          $_SESSION['user'] = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
          ];

          $this->flash('success', 'Successfully logged in');
          $this->redirect('index.php');
        } else {
          $this->flash('error', 'Wrong email or password');
          $this->render('auth/login', compact('title', 'flash', 'data'));
        }
      } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $this->flash('error', 'Unable to login. Please try again later.');
        $this->render('auth/login', compact('title', 'flash'));
      }
    } else {
      $this->render('auth/login', compact('title', 'flash'));
    }
  }

  public function register(): void
  {
    if ($this->checkAuth()) {
      $this->redirect('index.php');
    }

    $title = 'Register';
    $flash = $this->getFlash();

    if ($this->isPost()) {
      if (!$this->checkRateLimit('registration', 3, 600)) {
        $remainingTime = $this->getRateLimitRemainingTime('registration');
        $this->flash('error', "Too many registration attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.");
        $this->render('auth/register', compact('title', 'flash'));
        return;
      }

      if (!$this->validateCsrfToken()) {
        $this->flash('error', 'Security validation failed');
        $this->render('auth/register', compact('title', 'flash'));
        return;
      }

      $data = $this->getPostData(['name', 'email', 'password']);

      try {
        $user = new User($data);
        $user->save();

        $this->flash('success', 'You have successfully registered');
        $this->redirect('login.php');
      } catch (Exception $e) {
        $this->flash('error', $e->getMessage());
        $this->render('auth/register', compact('title', 'flash', 'data'));
      }
    } else {
      $this->render('auth/register', compact('title', 'flash'));
    }
  }

  public function logout(): void
  {
    // Clear all session data
    $_SESSION = [];

    // Clear the session cookie
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
      );
    }

    // Destroy the session
    session_destroy();

    // Clear session ID from global scope
    session_id('');

    $this->flash('success', 'You have been logged out');
    $this->redirect('index.php');
  }
}
