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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $ad_url = trim($_POST['ad_url']);
    $domains = trim($_POST['domains']);
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // Check if username or email already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Username or email already exists";
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Insert user
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            // Get the new user's ID
            $userId = $db->lastInsertId();
            
            // Insert video settings
            $stmt = $db->prepare("INSERT INTO video_settings (user_id, ad_url, domains) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $ad_url, $domains]);
            
            $db->commit();
            
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'User created successfully'];
            header("Location: /users");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Failed to create user. Please try again.";
        }
    }
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
                <h1 class="text-3xl font-bold text-white">Add New User</h1>
                <a href="/users" class="btn-secondary">Back to Users</a>
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
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">
                        Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                           required
                           class="input-field w-full">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                           required
                           class="input-field w-full">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                        Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="input-field w-full">
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-gray-300 mb-2">
                        Role
                    </label>
                    <select id="role" 
                            name="role" 
                            class="input-field w-full">
                        <option value="user" <?php echo (isset($role) && $role === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div>
                    <label for="ad_url" class="block text-sm font-medium text-gray-300 mb-2">
                        Advertisement URL
                    </label>
                    <input type="url" 
                           id="ad_url" 
                           name="ad_url" 
                           value="<?php echo isset($ad_url) ? htmlspecialchars($ad_url) : ''; ?>"
                           class="input-field w-full"
                           placeholder="https://example.com/ads">
                </div>

                <div>
                    <label for="domains" class="block text-sm font-medium text-gray-300 mb-2">
                        Allowed Domains
                    </label>
                    <textarea id="domains" 
                              name="domains" 
                              rows="3" 
                              class="input-field w-full"
                              placeholder="example.com&#10;subdomain.example.com"><?php echo isset($domains) ? htmlspecialchars($domains) : ''; ?></textarea>
                    <p class="mt-1 text-sm text-gray-400">Enter one domain per line</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/users" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
