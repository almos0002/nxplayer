<?php
require_once 'config.php';
requireLogin();

$flash_message = null;
if (isset($_SESSION['flash'])) {
    $flash_message = $_SESSION['flash'];
    $flash_type = $_SESSION['flash_type'];
    unset($_SESSION['flash']);
    unset($_SESSION['flash_type']);
}

if (!isset($_GET['id'])) {
    $_SESSION['flash'] = 'No video ID provided';
    $_SESSION['flash_type'] = 'error';
    header('Location: /dashboard');
    exit;
}

$video_id = intval($_GET['id']);

// Fetch video details
try {
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->execute([$video_id, $_SESSION['user_id']]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        $_SESSION['flash'] = 'Video not found';
        $_SESSION['flash_type'] = 'error';
        header('Location: /dashboard');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = 'Error fetching video: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: /dashboard');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $file_id = $_POST['file_id'] ?? '';
    $subtitle = $_POST['subtitle'] ?? '';

    if (empty($title) || empty($file_id)) {
        $_SESSION['flash'] = 'Title and File ID are required';
        $_SESSION['flash_type'] = 'error';
        header('Location: /edit?id=' . $video_id);
        exit;
    }

    try {
        // Check if file_id exists for other videos
        $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE file_id = ? AND id != ?");
        $stmt->execute([$file_id, $video_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['flash'] = 'File ID already exists';
            $_SESSION['flash_type'] = 'error';
            header('Location: /edit?id=' . $video_id);
            exit;
        }

        $stmt = $db->prepare("UPDATE videos SET title = ?, file_id = ?, subtitle = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $file_id, $subtitle, $video_id, $_SESSION['user_id']]);
        
        $_SESSION['flash'] = 'Video updated successfully';
        $_SESSION['flash_type'] = 'success';
        header('Location: /dashboard');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash'] = 'Failed to update video: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: /edit?id=' . $video_id);
        exit;
    }
}

require_once 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="card">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Edit Video</h1>
                <p class="text-gray-400">Update your video details</p>
            </div>

            <?php if ($flash_message): ?>
            <div class="<?php echo $flash_type === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6">
                <?php 
                echo htmlspecialchars($flash_message);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/edit?id=<?php echo $video_id; ?>" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Title</label>
                    <input type="text" name="title" required
                           class="input-field w-full"
                           value="<?php echo htmlspecialchars($video['title'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">File ID</label>
                    <input type="text" name="file_id" required
                           class="input-field w-full"
                           value="<?php echo htmlspecialchars($video['file_id'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Subtitle (Optional)</label>
                    <textarea name="subtitle" rows="3"
                              class="input-field w-full"><?php echo htmlspecialchars($video['subtitle'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-between">
                    <a href="/dashboard" class="btn-secondary">Back to Dashboard</a>
                    <button type="submit" class="btn-primary">Update Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
