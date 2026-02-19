<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2">
    <div class="bg-white rounded-lg shadow-lg">
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-t-lg">
        <h2 class="text-2xl font-bold flex items-center">
          <i class="fas fa-comments mr-3"></i>Guestbook Messages
        </h2>
      </div>
      <div class="p-6">
        <?php if ($user): ?>
          <form method="POST" action="/" class="mb-6">
            <?= $this->csrfTokenField() ?>
            <input type="hidden" name="send-message" value="1">

            <div class="mb-4">
              <label for="message" class="block text-gray-700 font-medium mb-2">Share your thoughts</label>
              <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                id="message" name="message" rows="4" placeholder="Write your message here..."></textarea>
            </div>

            <div class="flex justify-between items-center">
              <small class="text-gray-500">Maximum 1000 characters</small>
              <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-2 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-paper-plane mr-2"></i>Post Message
              </button>
            </div>
          </form>
        <?php else: ?>
          <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-6">
            <i class="fas fa-info-circle mr-2"></i>
            Please <a href="/login" class="font-medium text-blue-600 hover:text-blue-800">login</a> or
            <a href="/register" class="font-medium text-blue-600 hover:text-blue-800">register</a> to post a message.
          </div>
        <?php endif; ?>

        <hr class="my-6 border-gray-200">

        <?php if (empty($messages)): ?>
          <div class="text-center text-gray-500 py-8">
            <i class="fas fa-inbox text-6xl mb-4 text-gray-400"></i>
            <p class="text-xl">No messages yet. Be the first to leave a message!</p>
          </div>
        <?php else: ?>
          <div class="space-y-4">
            <?php foreach ($messages as $message): ?>
              <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h6 class="text-lg font-semibold text-gray-900 flex items-center">
                      <i class="fas fa-user-circle text-blue-600 mr-3 text-2xl"></i>
                      <?= htmlspecialchars($message->getUser()->getName()) ?>
                    </h6>
                    <small class="text-gray-500 flex items-center">
                      <i class="fas fa-clock mr-2"></i>
                      <?= htmlspecialchars($message->getCreatedAt()) ?>
                    </small>
                  </div>
                  <div class="flex gap-2">
                    <?php if ($message->getStatus() == 0): ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-eye-slash mr-1"></i>Pending
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-eye mr-1"></i>Active
                      </span>
                    <?php endif; ?>

                    <?php if ($user): ?>
                      <?php if ($user['id'] == $message->getUserId() || $this->checkAdmin()): ?>
                        <button class="bg-blue-100 text-blue-700 px-3 py-1 rounded-md hover:bg-blue-200 transition-colors edit-btn"
                          data-message-id="<?= $message->getId() ?>"
                          data-message-text="<?= htmlspecialchars($message->getMessage()) ?>"
                          data-bs-toggle="modal"
                          data-bs-target="#editModal">
                          <i class="fas fa-edit"></i>
                        </button>
                      <?php endif; ?>

                      <?php if ($this->checkAdmin()): ?>
                        <a href="/?do=toggle-status&id=<?= $message->getId() ?>&status=<?= $message->getStatus() ?>&page=<?= $page ?? 1 ?>"
                          class="bg-<?= $message->getStatus() ? 'red' : 'green' ?>-100 text-<?= $message->getStatus() ? 'red' : 'green' ?>-700 px-3 py-1 rounded-md hover:bg-<?= $message->getStatus() ? 'red' : 'green' ?>-200 transition-colors"
                          onclick="return confirm('Are you sure you want to <?= $message->getStatus() ? 'hide' : 'show' ?> this message?')">
                          <i class="fas fa-<?= $message->getStatus() ? 'eye-slash' : 'eye' ?>"></i>
                        </a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="text-gray-700 whitespace-pre-wrap">
                  <?= htmlspecialchars($message->getMessage()) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($pagination->count_pages > 1): ?>
      <nav aria-label="Messages pagination" class="mt-6">
        <?= $pagination ?>
      </nav>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Message Modal -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden" id="editModal">
  <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
    <div class="mt-3">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Edit Message</h3>
        <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('editModal').classList.add('hidden')">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>

      <form method="POST" action="/">
        <?= $this->csrfTokenField() ?>
        <input type="hidden" name="edit-message" value="1">
        <input type="hidden" name="id" id="edit-message-id">
        <input type="hidden" name="page" value="<?= $page ?? 1 ?>">

        <div class="mb-4">
          <label for="edit-message-text" class="block text-gray-700 font-medium mb-2">Message</label>
          <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            id="edit-message-text" name="message" rows="4"></textarea>
        </div>

        <div class="flex justify-end space-x-3">
          <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
            onclick="document.getElementById('editModal').classList.add('hidden')">
            Cancel
          </button>
          <button type="submit" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-2 px-4 rounded hover:from-blue-700 hover:to-purple-700 transition-all duration-200">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-btn');
    const editModal = document.getElementById('editModal');
    const editMessageId = document.getElementById('edit-message-id');
    const editMessageText = document.getElementById('edit-message-text');

    editButtons.forEach(button => {
      button.addEventListener('click', function() {
        const messageId = this.getAttribute('data-message-id');
        const messageText = this.getAttribute('data-message-text');

        editMessageId.value = messageId;
        editMessageText.value = messageText;
        editModal.classList.remove('hidden');
      });
    });

    // Close modal when clicking outside
    editModal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.add('hidden');
      }
    });

    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        editModal.classList.add('hidden');
      }
    });
  });
</script>