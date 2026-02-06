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
          <a href="index.php" class="flex items-center space-x-2 text-white font-bold text-xl">
            <i class="fas fa-book"></i>
            <span>Guestbook</span>
          </a>
        </div>
        <div class="hidden md:flex items-center space-x-4">
          <a href="index.php" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
            <i class="fas fa-home"></i>
            <span>Home</span>
          </a>
          <?php if (!isset($_SESSION['user'])): ?>
            <a href="register.php" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
              <i class="fas fa-user-plus"></i>
              <span>Register</span>
            </a>
            <a href="login.php" class="text-white hover:text-gray-200 transition-colors flex items-center space-x-1">
              <i class="fas fa-sign-in-alt"></i>
              <span>Login</span>
            </a>
          <?php endif; ?>
          <?php if (isset($_SESSION['user'])): ?>
            <div class="relative group">
              <button class="flex items-center space-x-2 text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                <?php if ($_SESSION['user']['role'] == 2): ?>
                  <span class="bg-yellow-500 text-xs px-2 py-1 rounded-full">Admin</span>
                <?php endif; ?>
                <i class="fas fa-chevron-down ml-1 text-sm"></i>
              </button>
              <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                <a href="login.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center space-x-2">
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
    if (isset($_SESSION['flash'])): ?>
      <div class="bg-<?= $_SESSION['flash']['type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $_SESSION['flash']['type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $_SESSION['flash']['type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash']['message']) ?></span>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?php
    // Display session messages
    if (isset($_SESSION['success'])): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success']) ?></span>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errors'])): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline"><?= $_SESSION['errors'] ?></span>
      </div>
      <?php unset($_SESSION['errors']); ?>
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