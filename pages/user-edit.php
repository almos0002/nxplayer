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

// Get user ID from query string
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId === 0) {
    header("Location: /users");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $ad_url = trim($_POST['ad_url']);
    $domains = trim($_POST['domains']);
    $newPassword = trim($_POST['password']);

    // Update user details
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $hashedPassword, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $userId]);
    }

    // Update video settings
    $stmt = $db->prepare("SELECT id FROM video_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE video_settings SET ad_url = ?, domains = ? WHERE user_id = ?");
        $stmt->execute([$ad_url, $domains, $userId]);
    } else {
        $stmt = $db->prepare("INSERT INTO video_settings (user_id, ad_url, domains) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $ad_url, $domains]);
    }

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'User updated successfully'];
    header("Location: /users");
    exit;
}

// Get user data
$stmt = $db->prepare("
    SELECT u.*, vs.ad_url, vs.domains 
    FROM users u 
    LEFT JOIN video_settings vs ON u.id = vs.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: /users");
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
                <h1 class="text-3xl font-bold text-gray-800">Edit User</h1>
                <a href="/users" class="btn-secondary">Back to Users</a>
            </div>
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

        <div class="card">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>"
                           required
                           class="form-input w-full"
                           style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           required
                           class="form-input w-full"
                           style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;">
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                        Role
                    </label>
                    <select id="role" 
                            name="role" 
                            class="form-input w-full"
                            style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                            <?php echo $user['id'] == 1 ? 'disabled' : ''; ?>>
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <?php if ($user['id'] == 1): ?>
                        <p class="mt-1 text-sm text-gray-500">The role of the first user cannot be changed</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-input w-full"
                           style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                           placeholder="Leave blank to keep current password">
                </div>

                <div>
                    <label for="ad_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Advertisement URL
                    </label>
                    <input type="url" 
                           id="ad_url" 
                           name="ad_url" 
                           value="<?php echo htmlspecialchars($user['ad_url'] ?? ''); ?>"
                           class="form-input w-full"
                           style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                           placeholder="https://example.com/ads">
                </div>

                <div>
                    <label for="domains" class="block text-sm font-medium text-gray-700 mb-2">
                        Allowed Domains
                    </label>
                    <textarea id="domains" 
                              name="domains" 
                              rows="3" 
                              class="form-input w-full"
                              style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                              placeholder="example.com&#10;subdomain.example.com"><?php echo htmlspecialchars($user['domains'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Enter one domain per line</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/users" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>