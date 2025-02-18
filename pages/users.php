<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Check if user is admin
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    header("Location: /dashboard");
    exit;
}

// Handle delete action
if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    // Don't allow deleting the first admin user
    if ($userId != 1) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'User deleted successfully'];
        header("Location: /users");
        exit;
    }
}

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the base query
$query = "SELECT u.*, 
          vs.ad_url, vs.domains,
          COUNT(v.id) as video_count 
          FROM users u 
          LEFT JOIN video_settings vs ON u.id = vs.user_id 
          LEFT JOIN videos v ON u.id = v.user_id";

$params = [];
if (!empty($search)) {
    $query .= " WHERE u.username LIKE ? OR u.email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$query .= " GROUP BY u.id ORDER BY u.id ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php require_once __DIR__ . '/../header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-white">Users Management</h1>
        <a href="/add-user" class="btn-primary">Add New User</a>
    </div>

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

    <div class="card mb-6">
        <form method="GET" class="flex gap-4">
            <input type="text" 
                   name="search" 
                   placeholder="Search by username or email" 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="input-field flex-grow">
            <button type="submit" class="btn-primary">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/users" class="btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="px-4 py-2 text-left text-gray-300">ID</th>
                        <th class="px-4 py-2 text-left text-gray-300">Username</th>
                        <th class="px-4 py-2 text-left text-gray-300">Email</th>
                        <th class="px-4 py-2 text-left text-gray-300">Role</th>
                        <th class="px-4 py-2 text-left text-gray-300">Videos</th>
                        <th class="px-4 py-2 text-left text-gray-300">Created At</th>
                        <th class="px-4 py-2 text-right text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-700 hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo htmlspecialchars($user['video_count']); ?></td>
                            <td class="px-4 py-2 text-gray-300"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex justify-end space-x-2">
                                    <a href="/user-edit?id=<?php echo $user['id']; ?>" 
                                       class="btn-secondary btn-sm">Edit</a>
                                    <?php if ($user['id'] != 1): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
