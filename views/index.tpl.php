<?php require_once __DIR__ . '/incs/header.tpl.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-20">
    <div class="mb-16">

        <?php if (isset($_SESSION['errors'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php
                echo $_SESSION['errors'];
                unset($_SESSION['errors']);
                ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                    </svg>
                </button>
            </div>
        <?php endif; ?>

    </div>

    <?php if (check_auth()): ?>
        <form method="post" class="mb-8">
            <div class="relative">
                <textarea name="message" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Leave a comment here" id="send-message" rows="4"></textarea>
                <label for="send-message" class="absolute -top-2 left-2 -mt-px inline-block px-1 bg-white text-xs font-medium text-gray-900">Comments</label>
            </div>
            <button name="send-message" type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Send</button>
        </form>

        <hr class="my-8">
    <?php endif; ?>

    <?php if (!empty($messages)): ?>
        <div class="mb-8">
            <?= $pagination ?>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="bg-white rounded-lg shadow-md <?php if (!$message['status']) echo 'border-l-4 border-red-500' ?> p-6"
                    id="message-<?= $message['id'] ?>">

                    <div class="flex justify-between items-start mb-4">
                        <h5 class="text-xl font-bold text-gray-900"><?= $message['name'] ?></h5>
                        <p class="text-sm text-gray-500"><?= $message['created_at'] ?></p>
                    </div>

                    <div class="text-gray-700 mb-4">
                        <?= nl2br(h($message['message'])) ?>
                    </div>

                    <?php if (check_admin()): ?>
                        <div class="border-t pt-4">
                            <div class="flex space-x-4 mb-4">
                                <?php if ($message['status'] == 1): ?>
                                    <a href="?page=<?= $page ?>&do=toggle-status&status=0&id=<?= $message['id'] ?>" class="text-red-600 hover:text-red-800">Disable</a>
                                <?php else: ?>
                                    <a href="?page=<?= $page ?>&do=toggle-status&status=1&id=<?= $message['id'] ?>" class="text-green-600 hover:text-green-800">Approve</a>
                                <?php endif; ?>
                                <button onclick="toggleEdit(<?= $message['id'] ?>)" class="text-blue-600 hover:text-blue-800">Edit</button>
                            </div>

                            <div id="edit-form-<?= $message['id'] ?>" class="hidden">
                                <form method="post">
                                    <div class="relative mb-4">
                                        <textarea name="message" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Leave a comment here"
                                            id="text-<?= $message['id'] ?>" rows="6"><?= $message['message'] ?></textarea>
                                        <label for="text-<?= $message['id'] ?>" class="absolute -top-2 left-2 -mt-px inline-block px-1 bg-white text-xs font-medium text-gray-900">Comments</label>
                                    </div>
                                    <input type="hidden" name="id" value="<?= $message['id'] ?>">
                                    <input type="hidden" name="page" value="<?= $_GET['page'] ?? 1 ?>">
                                    <button name="edit-message" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">Messages not found...</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($messages)): ?>
        <div class="mt-8">
            <?= $pagination ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function toggleEdit(id) {
        const form = document.getElementById('edit-form-' + id);
        form.classList.toggle('hidden');
    }
</script>

<?php require_once __DIR__ . '/incs/footer.tpl.php'; ?>