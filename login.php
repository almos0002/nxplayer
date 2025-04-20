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
                $_SESSION['role'] = $user['role']; // Store user role in session
                $_SESSION['last_activity'] = time(); // For session timeout tracking
                $_SESSION['remember_me'] = true; // Flag this as a remember-me session
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

// Check for session expiration
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = "Your session has expired. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Store user role in session
                $_SESSION['last_activity'] = time(); // For session timeout tracking
                
                // Handle remember me
                if ($rememberMe) {
                    $_SESSION['remember_me'] = true; // Flag this as a remember-me session
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
                } else {
                    $_SESSION['remember_me'] = false; // Regular session - will expire after inactivity
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

<div class="flex items-center justify-center min-h-[85vh]">
    <div class="w-full max-w-md">
        <div class="card w-full shadow-elevated border-0 overflow-hidden">
            <!-- Card header -->
            <div class="px-8 pt-8 pb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Welcome back</h2>
                <p class="text-gray-500">Please sign in to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="mx-8 mb-6 bg-red-50 border-l-4 border-red-500 text-red-600 px-5 py-4 rounded-r-lg flex items-start">
                <i class="fa-duotone fa-thin fa-circle-exclamation mr-3 mt-0.5 text-red-500"></i>
                <span class="text-sm"><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="px-8 pb-8">
                <div class="space-y-5">
                    <!-- Username field with icon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-user text-gray-400 text-sm"></i>
                            </div>
                            <input type="text" name="username" required
                                class="form-input w-full pl-10"
                                placeholder="Enter your username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Password field with icon -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <a href="#" class="text-xs text-primary hover:text-teal-800">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-lock text-gray-400 text-sm"></i>
                            </div>
                            <input type="password" name="password" required
                                class="form-input w-full pl-10"
                                placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <!-- Remember me checkbox -->
                    <div class="flex items-center">
                        <input type="checkbox" id="remember_me" name="remember_me" 
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary focus:ring-offset-white">
                        <label for="remember_me" class="ml-2 text-sm text-gray-600">
                            Remember me for 30 days
                        </label>
                    </div>

                    <!-- Sign in button -->
                    <button type="submit" class="btn-primary w-full hover-lift flex items-center justify-center">
                        <i class="fa-duotone fa-thin fa-sign-in mr-2"></i>
                        <span>Sign In</span>
                    </button>
                </div>

                <!-- Register link -->
                <div class="text-center text-gray-600 text-sm mt-8 pt-6 border-t border-gray-100">
                    Don't have an account? 
                    <a href="/register" class="text-primary hover:text-teal-800 font-medium">
                        Register here
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
