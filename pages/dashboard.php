<?php
require_once 'config.php';
requireLogin();

$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        try {
            $stmt = $db->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Video deleted successfully'];
            header('Location: /dashboard');
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to delete video: ' . $e->getMessage()];
            header('Location: /dashboard');
            exit;
        }
    } else {
        $title = $_POST['title'] ?? '';
        $file_id = $_POST['file_id'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';

        if (empty($title) || empty($file_id)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Title and File ID are required'];
            header('Location: /dashboard');
            exit;
        } else {
            try {
                // Check for duplicate file_id
                $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE file_id = ?");
                $stmt->execute([$file_id]);
                if ($stmt->fetchColumn() > 0) {
                    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'File ID already exists'];
                    header('Location: /dashboard');
                    exit;
                } else {
                    // Generate random unique slug
                    function generateRandomSlug($length = 6) {
                        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        $slug = '';
                        for ($i = 0; $i < $length; $i++) {
                            $slug .= $characters[random_int(0, strlen($characters) - 1)];
                        }
                        return $slug;
                    }

                    $slug = generateRandomSlug();
                    while (true) {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE slug = ?");
                        $stmt->execute([$slug]);
                        if ($stmt->fetchColumn() == 0) break;
                        $slug = generateRandomSlug(); // Generate a new slug if collision occurs
                    }

                    $stmt = $db->prepare("INSERT INTO videos (user_id, title, file_id, subtitle, slug) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $title, $file_id, $subtitle, $slug]);
                    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Video added successfully'];
                    header('Location: /dashboard');
                    exit;
                }
            } catch (PDOException $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to add video: ' . $e->getMessage()];
                header('Location: /dashboard');
                exit;
            }
        }
    }
}

// Get total videos count for pagination
try {
    $where_clause = "";
    $params = [$_SESSION['user_id']];
    
    if (!empty($search)) {
        $where_clause = " AND (title LIKE ? OR file_id LIKE ? OR subtitle LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ?" . $where_clause);
    $stmt->execute($params);
    $total_videos = $stmt->fetchColumn();
    $total_pages = ceil($total_videos / $limit);
    
    // Get videos for current page
    $query = sprintf("SELECT * FROM videos WHERE user_id = ? %s ORDER BY id DESC LIMIT %d OFFSET %d", 
                    $where_clause, $limit, $offset);
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Failed to fetch videos: ' . $e->getMessage()];
    header('Location: /dashboard');
    exit;
}

require_once 'header.php';
?>
<?php if ($flash_message): ?>
                <div id="flash-message" class="<?php echo $flash_message['type'] === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6 transform transition-all duration-500 ease-in-out">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <?php if ($flash_message['type'] === 'error'): ?>
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            <?php else: ?>
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm leading-5">
                                <?php echo htmlspecialchars($flash_message['text']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <script>
                    // Animate flash message
                    const flashMessage = document.getElementById('flash-message');
                    if (flashMessage) {
                        // Fade in
                        flashMessage.style.opacity = '0';
                        flashMessage.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            flashMessage.style.opacity = '1';
                            flashMessage.style.transform = 'translateY(0)';
                        }, 100);

                        // Fade out after delay
                        setTimeout(() => {
                            flashMessage.style.opacity = '0';
                            flashMessage.style.transform = 'translateY(-10px)';
                            setTimeout(() => {
                                flashMessage.style.display = 'none';
                            }, 500);
                        }, 5000);
                    }
                </script>
                <?php endif; ?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Video Form -->
        <div class="lg:col-span-1">
            <div class="card">
                <h2 class="text-2xl font-bold text-white mb-6">Add New Video</h2>
                
                <form method="POST" action="/dashboard" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Title</label>
                        <input type="text" name="title" required
                               class="input-field w-full"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">File ID</label>
                        <input type="text" name="file_id" required
                               class="input-field w-full"
                               value="<?php echo htmlspecialchars($_POST['file_id'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Subtitle (Optional)</label>
                        <input type="text" name="subtitle"
                               class="input-field w-full"
                               value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Add Video
                    </button>
                </form>
            </div>
        </div>

        <!-- Video List -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Your Videos</h2>
                    
                    <!-- Search Form -->
                    <form method="GET" class="flex">
                        <input type="text" name="search" 
                               class="input-field w-64"
                               placeholder="Search videos..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-primary ml-2">
                            Search
                        </button>
                    </form>
                </div>

                <?php if (empty($videos)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-400">
                        <?php echo empty($search) ? 'No videos added yet.' : 'No videos found matching your search.'; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left border-b border-gray-700">
                                <th class="pb-3 text-gray-400 font-medium">Title</th>
                                <th class="pb-3 text-gray-400 font-medium">File ID</th>
                                <th class="pb-3 text-gray-400 font-medium">Subtitle</th>
                                <th class="pb-3 text-gray-400 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $video): ?>
                            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                                <td class="py-4 text-white">
                                    <?php echo htmlspecialchars($video['title']); ?>
                                </td>
                                <td class="py-4 text-gray-300">
                                    <?php echo htmlspecialchars($video['file_id']); ?>
                                </td>
                                <td class="py-4 text-gray-300">
                                    <?php echo htmlspecialchars($video['subtitle'] ?: '-'); ?>
                                </td>
                                <td class="py-4 flex gap-2">
                                    <span class="icon-btn" onclick="copyVideoUrl('<?php echo htmlspecialchars($video['slug']); ?>')" title="Copy URL">
                                        <i class="fas fa-link icon-copy"></i>
                                    </span>
                                    <a href="/edit?id=<?php echo $video['id']; ?>" class="icon-btn" title="Edit Video">
                                        <i class="fas fa-edit icon-copy"></i>
                                    </a>
                                    <form method="POST" action="/dashboard" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                                        <button type="submit" class="icon-btn" title="Delete Video">
                                            <i class="fas fa-trash icon-delete"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-6">
                    <div class="flex justify-center space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="/dashboard?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                First
                            </a>
                            <a href="/dashboard?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, min($page - 2, $total_pages - 4));
                        $end = min($total_pages, max($page + 2, 5));
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="/dashboard?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 text-sm font-medium <?php echo $i === $page ? 'text-blue-400 bg-gray-900' : 'text-white bg-gray-800 hover:bg-gray-700'; ?> rounded-md">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="/dashboard?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                Next
                            </a>
                            <a href="/dashboard?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                Last
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-4 text-sm text-gray-400">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        (<?php echo $total_videos; ?> total videos)
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Copy URL Success Message -->
<div id="copySuccess" class="fixed top-8 right-4 bg-green-900/90 text-green-200 px-4 py-2 rounded-lg shadow-lg transform transition-opacity duration-300 opacity-0">
    URL copied to clipboard!
</div>

<!-- Add JavaScript for copy functionality -->
<script>
function copyVideoUrl(slug) {
    const url = `${window.location.origin}/${slug}`;
    navigator.clipboard.writeText(url).then(() => {
        const copySuccess = document.getElementById('copySuccess');
        copySuccess.style.opacity = '1';
        setTimeout(() => {
            copySuccess.style.opacity = '0';
        }, 2000);
    });
}
</script>

<?php require_once 'footer.php'; ?>
