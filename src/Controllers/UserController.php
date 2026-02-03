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
    $errors = $this->getErrors();
    $success = $this->getSuccess();

    if ($this->isPost()) {
      if (!$this->checkRateLimit('login_attempt', 5, 300)) {
        $remainingTime = $this->getRateLimitRemainingTime('login_attempt');
        $errors = "Too many login attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
        $this->render('auth/login', compact('title', 'errors', 'success'));
        return;
      }

      if (!$this->validateCsrfToken()) {
        $errors = 'Security validation failed';
        $this->render('auth/login', compact('title', 'errors', 'success'));
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
          $this->setErrors('Wrong email or password');
          $this->render('auth/login', compact('title', 'errors', 'success', 'data'));
        }
      } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $this->flash('error', 'Unable to login. Please try again later.');
        $this->render('auth/login', compact('title', 'errors', 'success'));
      }
    } else {
      $this->render('auth/login', compact('title', 'errors', 'success'));
    }
  }

  public function register(): void
  {
    if ($this->checkAuth()) {
      $this->redirect('index.php');
    }

    $title = 'Register';
    $errors = $this->getErrors();
    $success = $this->getSuccess();

    if ($this->isPost()) {
      if (!$this->checkRateLimit('registration', 3, 600)) {
        $remainingTime = $this->getRateLimitRemainingTime('registration');
        $errors = "Too many registration attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
        $this->render('auth/register', compact('title', 'errors', 'success'));
        return;
      }

      if (!$this->validateCsrfToken()) {
        $errors = 'Security validation failed';
        $this->render('auth/register', compact('title', 'errors', 'success'));
        return;
      }

      $data = $this->getPostData(['name', 'email', 'password']);

      try {
        $user = new User($data);
        $user->save();

        $success = 'You have successfully registered';
        $this->redirect('login.php');
      } catch (Exception $e) {
        $errors = $e->getMessage();
        $this->render('auth/register', compact('title', 'errors', 'success', 'data'));
      }
    } else {
      $this->render('auth/register', compact('title', 'errors', 'success'));
    }
  }

  public function logout(): void
  {
    session_destroy();
    $this->flash('success', 'You have been logged out');
    $this->redirect('index.php');
  }
}
