<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Get video ID from query string
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'No video ID provided'];
    header("Location: /dashboard");
    exit;
}

$videoId = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $file_id = trim($_POST['file_id']);
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($file_id)) {
        $errors[] = "File ID is required";
    }
    
    if (empty($errors)) {
        // Generate a slug from the title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        // Update existing video
        $stmt = $db->prepare("UPDATE videos SET title = ?, subtitle = ?, file_id = ?, slug = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $subtitle, $file_id, $slug, $videoId, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Video updated successfully'];
            header("Location: /dashboard");
            exit;
        } else {
            $errors[] = "Failed to update video or no changes made";
        }
    }
}

// Get video data
$stmt = $db->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
$stmt->execute([$videoId, $_SESSION['user_id']]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Video not found'];
    header("Location: /dashboard");
    exit;
}

// Get flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php require_once __DIR__ . '/../header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Edit Video</h1>
                <a href="/dashboard" class="btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($flash_message): ?>
            <div id="flash-message" class="<?php echo $flash_message['type'] === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($flash_message['text']); ?>
            </div>
            <script>
                setTimeout(() => {
                    const flashMessage = document.getElementById('flash-message');
                    if (flashMessage) {
                        flashMessage.style.transition = 'opacity 0.5s ease-out';
                        flashMessage.style.opacity = '0';
                        setTimeout(() => {
                            flashMessage.remove();
                        }, 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-300 mb-2">
                        Title
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($video['title']); ?>"
                           required
                           class="input-field w-full"
                           placeholder="Enter video title">
                </div>

                <div>
                    <label for="subtitle" class="block text-sm font-medium text-gray-300 mb-2">
                        Subtitle
                    </label>
                    <input type="text" 
                           id="subtitle" 
                           name="subtitle" 
                           value="<?php echo htmlspecialchars($video['subtitle'] ?? ''); ?>"
                           class="input-field w-full"
                           placeholder="Enter video subtitle (optional)">
                </div>

                <div>
                    <label for="file_id" class="block text-sm font-medium text-gray-300 mb-2">
                        File ID
                    </label>
                    <input type="text" 
                           id="file_id" 
                           name="file_id" 
                           value="<?php echo htmlspecialchars($video['file_id']); ?>"
                           required
                           class="input-field w-full"
                           placeholder="Enter video file ID">
                    <p class="mt-1 text-sm text-gray-400">This is the unique identifier for your video file</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/dashboard" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
