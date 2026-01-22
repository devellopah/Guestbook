<?php

if (isset($_GET['do']) && $_GET['do'] == 'logout') {
    unset($_SESSION['user']);
    redirect('login.php');
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GuestBook :: <?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>

    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.php" class="text-white text-xl font-bold">Home</a>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-4">
                    <?php if (!check_auth()): ?>
                        <a href="register.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Register</a>
                        <a href="login.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <?php else: ?>
                        <div class="relative">
                            <button id="user-menu-button" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium flex items-center">
                                Hello, <?= $_SESSION['user']['name'] ?>
                                <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="?do=logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-300 hover:text-white p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-800 border-t border-gray-700">
                    <?php if (!check_auth()): ?>
                        <a href="register.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Register</a>
                        <a href="login.php" class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Login</a>
                    <?php else: ?>
                        <div class="border-t border-gray-700 pt-4 pb-3">
                            <div class="flex items-center px-5">
                                <div class="text-base font-medium text-white">Hello, <?= $_SESSION['user']['name'] ?></div>
                            </div>
                            <div class="mt-3 space-y-1 px-2">
                                <a href="?do=logout" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Logout</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // User dropdown toggle
        document.getElementById('user-menu-button')?.addEventListener('click', function() {
            document.getElementById('user-menu').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const userMenuButton = document.getElementById('user-menu-button');
            if (userMenu && userMenuButton && !userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>