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
            // Check if user is admin
            $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

            if ($isAdmin) {
                // Admins can delete any video
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->execute([$_POST['id']]);
            } else {
                // Regular users can only delete their own videos
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
            }

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
                    function generateRandomSlug($length = 6)
                    {
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

// Get current user's role
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();

// Get statistics based on user role
if ($userRole === 'admin') {
    // Admin statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos");
    $stmt->execute();
    $total_videos = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $videos_last_24h = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $new_users_week = $stmt->fetchColumn();
} else {
    // Regular user statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_total_videos = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute([$_SESSION['user_id']]);
    $user_videos_last_24h = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$_SESSION['user_id']]);
    $user_videos_last_week = $stmt->fetchColumn();
}

// Fetch videos based on user role
if ($userRole === 'admin') {
    // Admin sees all videos
    $where_clause = "";
    $params = [];

    if (!empty($search)) {
        $where_clause = " AND (title LIKE ? OR file_id LIKE ? OR subtitle LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE 1=1" . $where_clause);
    $stmt->execute($params);
    $total_videos = $stmt->fetchColumn();
    $total_pages = ceil($total_videos / $limit);

    // Get videos for current page
    $query = sprintf(
        "SELECT * FROM videos WHERE 1=1 %s ORDER BY id DESC LIMIT %d OFFSET %d",
        $where_clause,
        $limit,
        $offset
    );
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Regular users only see their own videos
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
    $query = sprintf(
        "SELECT * FROM videos WHERE user_id = ? %s ORDER BY id DESC LIMIT %d OFFSET %d",
        $where_clause,
        $limit,
        $offset
    );
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once 'header.php';
?>
<?php if ($flash_message): ?>
    <div id="flash-message" class="<?php echo $flash_message['type'] === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6 transform transition-all duration-500 ease-in-out">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <?php if ($flash_message['type'] === 'error'): ?>
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                <?php else: ?>
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
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
    <div class="mb-10">
        <div class="flex items-center mb-2">
            <span class="text-gray-500 text-sm">Welcome back,</span>
            <span class="text-primary font-medium text-sm ml-1"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <div class="flex items-center justify-between">
            <h1 class="page-title">Dashboard</h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <?php if ($userRole === 'admin'): ?>
            <!-- Admin Statistics -->
            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-teal-600 group-hover:bg-teal-700">
                        <i class="fa-duotone fa-thin fa-users"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Total Users</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    <span class="text-teal-600 font-medium">+<?php echo $new_users_week; ?></span> new this week
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-pink-500 group-hover:bg-pink-600">
                        <i class="fa-duotone fa-thin fa-film"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Total Videos</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_videos); ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    <span class="text-pink-500 font-medium">+<?php echo $videos_last_24h; ?></span> in last 24h
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-indigo-500 group-hover:bg-indigo-600">
                        <i class="fa-duotone fa-thin fa-users"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Videos per User</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_users > 0 ? number_format($total_videos / $total_users, 1) : '0'; ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Average per user
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-amber-500 group-hover:bg-amber-600">
                        <i class="fa-duotone fa-thin fa-clock"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Recent Activity</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo $videos_last_24h + $new_users_week; ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    New videos & users recently
                </div>
            </div>

        <?php else: ?>
            <!-- User Statistics -->
            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-teal-600 group-hover:bg-teal-700">
                        <i class="fa-duotone fa-thin fa-film"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Your Videos</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($user_total_videos); ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Total videos uploaded
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-pink-500 group-hover:bg-pink-600">
                        <i class="fa-duotone fa-thin fa-clock"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Recent Uploads</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($user_videos_last_24h); ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Uploaded in last 24h
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-indigo-500 group-hover:bg-indigo-600">
                        <i class="fa-duotone fa-thin fa-chart-bar"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Weekly Activity</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($user_videos_last_week); ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Videos this week
                </div>
            </div>

            <div class="stat-card group hover-lift">
                <div class="flex items-center">
                    <div class="stat-icon bg-amber-500 group-hover:bg-amber-600">
                        <i class="fa-duotone fa-thin fa-chart-line"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 font-medium">Growth Rate</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo $user_growth_rate > 0 ? '+' . $user_growth_rate . '%' : $user_growth_rate . '%'; ?></h3>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Compared to last month
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add Video Form -->
        <div class="lg:col-span-1">
            <div class="card">
                <h2 class="text-2xl font-bold text-white mb-6">Add New Video</h2>

                <form method="POST" action="/dashboard" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-heading text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="title" required
                                class="form-input w-full pl-10"
                                placeholder="Enter video title"
                                value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-file-video text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="file_id" required
                                class="form-input w-full pl-10"
                                placeholder="Enter file ID"
                                value="<?php echo htmlspecialchars($_POST['file_id'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle (Optional)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-closed-captioning text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="subtitle"
                                class="form-input w-full pl-10"
                                placeholder="Enter subtitle (optional)"
                                value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full hover-lift flex items-center justify-center mt-2">
                        <i class="fa-duotone fa-thin fa-plus mr-2"></i>
                        <span>Add Video</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Video List -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="flex flex-col lg:flex-row justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4 lg:mb-0"><?php echo $userRole === 'admin' ? 'All Videos' : 'Your Videos'; ?></h2>

                    <!-- Search Form -->
                    <form method="GET" class="flex flex-col lg:flex-row lg:items-center w-full lg:w-auto">
                        <div class="relative w-full lg:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-search text-gray-400"></i>
                            </div>
                            <input type="text" name="search"
                                class="form-input w-full pl-10 pr-10"
                                placeholder="Search videos..."
                                value="<?php echo htmlspecialchars($search); ?>">
                            <?php if (!empty($search)): ?>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <a href="/dashboard" class="text-gray-400 hover:text-gray-600">
                                    <i class="fa-duotone fa-thin fa-times"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary lg:ml-2 mt-4 lg:mt-0 flex items-center justify-center">
                            <i class="fa-duotone fa-thin fa-search mr-2"></i>Search
                        </button>
                    </form>
                </div>

                <?php if (empty($videos)): ?>
                    <div class="text-center py-12">
                        <div class="bg-gray-50 rounded-xl p-8 inline-block mb-4">
                            <i class="fa-duotone fa-thin fa-video-slash text-4xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-500 font-medium">
                            <?php echo empty($search) ? ($userRole === 'admin' ? 'No videos added yet.' : 'No videos added yet.') : 'No videos found matching your search.'; ?>
                        </p>
                        <?php if (!empty($search)): ?>
                            <a href="/dashboard" class="text-primary hover:text-teal-800 mt-2 inline-block">
                                <i class="fa-duotone fa-thin fa-arrow-left mr-1"></i> Clear search
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($videos as $video): ?>
                            <div class="bg-white rounded-xl overflow-hidden shadow-card hover:shadow-elevated transition-all duration-300 hover:-translate-y-1 border border-gray-100 flex flex-col h-[220px]">
                                <!-- Video Header -->
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center mr-2">
                                            <i class="fa-duotone fa-thin fa-film text-primary"></i>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500">Video</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="icon-btn" onclick="copyVideoUrl('<?php echo htmlspecialchars($video['slug']); ?>')" title="Copy link">
                                            <i class="fa-duotone fa-thin fa-copy icon-copy text-xs"></i>
                                        </button>
                                        <a href="/edit?id=<?php echo $video['id']; ?>" class="icon-btn" title="Edit video">
                                            <i class="fa-duotone fa-thin fa-pen-to-square text-xs"></i>
                                        </a>
                                        <form method="POST" action="/dashboard" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $video['id']; ?>">
                                            <button type="submit" class="icon-btn" title="Delete video">
                                                <i class="fa-duotone fa-thin fa-trash icon-delete text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Video Details -->
                                <div class="p-5 flex-grow flex flex-col justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2 line-clamp-2">
                                            <?php echo htmlspecialchars($video['title']); ?>
                                        </h3>
                                        <p class="text-gray-500 text-sm mb-3 line-clamp-2">
                                            <?php echo !empty($video['subtitle']) ? htmlspecialchars($video['subtitle']) : '&nbsp;'; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="flex justify-between items-center mt-auto pt-2 border-t border-gray-50">
                                        <span class="text-xs text-gray-400">
                                            Added: <?php echo date('M j, Y', strtotime($video['created_at'])); ?>
                                        </span>
                                        <a href="/<?php echo htmlspecialchars($video['slug']); ?>" class="text-primary hover:text-teal-800 text-sm font-medium flex items-center">
                                            View <i class="fa-duotone fa-thin fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-6">
                        <div class="flex justify-center space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="/dashboard?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                    First
                                </a>
                                <a href="/dashboard?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            if ($end_page - $start_page < 4 && $start_page > 1) {
                                $start_page = max(1, $end_page - 4);
                            }
                            ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="/dashboard?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-4 py-2 text-sm font-medium <?php echo $i === $page ? 'bg-primary text-white' : 'text-white bg-gray-800 hover:bg-gray-700'; ?> rounded-md">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="/dashboard?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                    Next
                                </a>
                                <a href="/dashboard?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">
                                    Last
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Video Card Grid -->
<script>
    function copyVideoUrl(slug) {
        const baseUrl = window.location.origin;
        const videoUrl = `${baseUrl}/${slug}`;

        // Create temporary input element
        const tempInput = document.createElement('input');
        tempInput.style.position = 'absolute';
        tempInput.style.left = '-1000px';
        tempInput.value = videoUrl;
        document.body.appendChild(tempInput);

        // Copy the text
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);

        // Show feedback
        const notification = document.createElement('div');
        notification.textContent = 'URL copied to clipboard';
        notification.className = 'fixed bottom-4 right-4 bg-primary text-white py-2 px-4 rounded-lg shadow-lg transform transition-all duration-300';
        document.body.appendChild(notification);

        // Remove notification after delay
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(10px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 2000);
    }
</script>

<?php require_once 'footer.php'; ?>