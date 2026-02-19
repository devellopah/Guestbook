<?php
// Global Exception Handler
set_exception_handler(function ($exception) {
  error_log("Uncaught exception: " . $exception->getMessage());
  http_response_code(500);
  echo "<h1>System Error</h1><p>We're experiencing technical difficulties. Please try again later.</p>";
  die();
});

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'Guestbook' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Custom styles for TailwindCSS compatibility */
    .message-content {
      white-space: pre-wrap;
      word-wrap: break-word;
    }
  </style>
</head>

<body>
  <nav class="bg-gradient-to-r from-blue-600 to-purple-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <a href="/" class="flex items-center space-x-2 text-white font-bold text-xl">
            <i class="fas fa-book"></i>
            <span>Guestbook</span>
          </a>
        </div>
        <div class="hidden md:flex items-center space-x-4">
          <a href="/" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
            <i class="fas fa-home"></i>
            <span>Home</span>
          </a>
          <?php if (!$user): ?>
            <a href="/register" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
              <i class="fas fa-user-plus"></i>
              <span>Register</span>
            </a>
            <a href="/login" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
              <i class="fas fa-sign-in-alt"></i>
              <span>Login</span>
            </a>
          <?php else: ?>
            <div class="relative group">
              <button class="flex items-center space-x-2 text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($user['name']) ?></span>
                <?php if ($user['role'] == 2): ?>
                  <span class="bg-yellow-500 text-xs px-2 py-1 rounded-full">Admin</span>
                <?php endif; ?>
                <i class="fas fa-chevron-down ml-1 text-sm"></i>
              </button>
              <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                <a href="/logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center space-x-2">
                  <i class="fas fa-sign-out-alt"></i>
                  <span>Logout</span>
                </a>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php
    // Display flash messages
    if (isset($flash)): ?>
      <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline"><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <!-- Render the specific view -->
    <?php require_once __DIR__ . "/../{$view}.php"; ?>
  </main>

  <footer class="bg-gray-800 text-white py-8 mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <p class="text-gray-400">&copy; <?= date('Y') ?> Guestbook Application. Built with PHP MVC Architecture.</p>
    </div>
  </footer>
</body>

</html>