<?php
require_once __DIR__ . '/../config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn() && !isset($_GET['admin'])) {
    header("Location: /dashboard");
    exit;
}

// For admin creating new user, check if current user is admin
if (isset($_GET['admin'])) {
    if (!isLoggedIn()) {
        header("Location: /login");
        exit;
    }
    
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userRole = $stmt->fetchColumn();
    
    if ($userRole !== 'admin') {
        header("Location: /dashboard");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'user';
    
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
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
            if (isset($_GET['admin'])) {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'User created successfully'];
                header("Location: /users");
            } else {
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Registration successful. Please log in.'];
                header("Location: /login");
            }
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// Get flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php require_once __DIR__ . '/../header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2"><?php echo isset($_GET['admin']) ? 'Create New User' : 'Register'; ?></h1>
            <p class="text-gray-400"><?php echo isset($_GET['admin']) ? 'Add a new user to the system' : 'Create your account'; ?></p>
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

                <?php if (isset($_GET['admin'])): ?>
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
                <?php endif; ?>

                <div class="flex justify-end space-x-4">
                    <?php if (isset($_GET['admin'])): ?>
                        <a href="/users" class="btn-secondary">Cancel</a>
                    <?php else: ?>
                        <a href="/login" class="btn-secondary">Back to Login</a>
                    <?php endif; ?>
                    <button type="submit" class="btn-primary">
                        <?php echo isset($_GET['admin']) ? 'Create User' : 'Register'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
