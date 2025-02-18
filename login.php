<?php
require_once 'config.php';

function generateRememberToken() {
    return bin2hex(random_bytes(32));
}

function setRememberMeCookie($userId, $token) {
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    setcookie('remember_token', $token, $expires, '/', '', true, true);
    setcookie('remember_user', $userId, $expires, '/', '', true, true);
}

function clearRememberMeCookies() {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
}

// Check for remember-me cookie first
if (!isLoggedIn() && isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    $token = $_COOKIE['remember_token'];
    $userId = $_COOKIE['remember_user'];
    
    try {
        $stmt = $db->prepare("SELECT user_id FROM remember_tokens WHERE token = ? AND user_id = ? AND expires_at > NOW()");
        $stmt->execute([$token, $userId]);
        
        if ($row = $stmt->fetch()) {
            // Token is valid, log the user in
            $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: /dashboard");
                exit;
            }
        }
    } catch (PDOException $e) {
        // Invalid token or expired, clear cookies
        clearRememberMeCookies();
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: /dashboard");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Handle remember me
                if ($rememberMe) {
                    $token = generateRememberToken();
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    // Delete any existing tokens for this user
                    $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Insert new token
                    $stmt = $db->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expires]);
                    
                    // Set cookies
                    setRememberMeCookie($user['id'], $token);
                }
                
                header("Location: /dashboard");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "Login failed: " . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<div class="flex items-center justify-center min-h-[80vh]">
    <div class="card w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Welcome Back</h1>
            <p class="text-gray-400">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-3 rounded-lg mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                <input type="text" name="username" required
                       class="input-field w-full"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <input type="password" name="password" required
                       class="input-field w-full">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="remember_me" name="remember_me" 
                       class="w-4 h-4 text-blue-600 border-gray-700 rounded focus:ring-blue-500 focus:ring-offset-gray-900">
                <label for="remember_me" class="ml-2 text-sm text-gray-400">
                    Remember me for 30 days
                </label>
            </div>

            <button type="submit" class="btn-primary w-full">
                Sign In
            </button>

            <div class="text-center text-gray-400 text-sm">
                Don't have an account? 
                <a href="/register" class="text-primary hover:text-orange-500 font-medium">
                    Register here
                </a>
            </div>
        </form>
    </div>
</div>
