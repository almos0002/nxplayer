<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: /dashboard");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Check if username exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username already exists";
                goto display_form;
            }

            // Check if email exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Email already registered";
                goto display_form;
            }

            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);

            // Log the user in
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $username;

            header("Location: /dashboard");
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

display_form:
require_once 'header.php';
?>

<div class="flex items-center justify-center min-h-[85vh]">
    <div class="w-full max-w-md">
        <div class="card w-full shadow-elevated border-0 overflow-hidden">
            <!-- Card header -->
            <div class="px-8 pt-8 pb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">Create Account</h2>
                <p class="text-gray-500">Join our video platform</p>
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
                                placeholder="Choose a username"
                                pattern="[a-zA-Z0-9_-]{3,50}"
                                title="Username can only contain letters, numbers, dashes and underscores (3-50 characters)"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Letters, numbers, dashes and underscores only (3-50 characters)</p>
                    </div>

                    <!-- Email field with icon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-envelope text-gray-400 text-sm"></i>
                            </div>
                            <input type="email" name="email" required
                                class="form-input w-full pl-10"
                                placeholder="Enter your email address"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Password field with icon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-lock text-gray-400 text-sm"></i>
                            </div>
                            <input type="password" name="password" required
                                class="form-input w-full pl-10"
                                placeholder="Create a password"
                                minlength="6"
                                title="Password must be at least 6 characters long">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long</p>
                    </div>

                    <!-- Confirm Password field with icon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-duotone fa-thin fa-shield-check text-gray-400 text-sm"></i>
                            </div>
                            <input type="password" name="confirm_password" required
                                class="form-input w-full pl-10"
                                placeholder="Confirm your password"
                                minlength="6">
                        </div>
                    </div>

                    <!-- Create account button -->
                    <button type="submit" class="btn-primary w-full hover-lift flex items-center justify-center mt-6">
                        <i class="fa-duotone fa-thin fa-user-plus mr-2"></i>
                        <span>Create Account</span>
                    </button>
                </div>

                <!-- Login link -->
                <div class="text-center text-gray-600 text-sm mt-8 pt-6 border-t border-gray-100">
                    Already have an account? 
                    <a href="/login" class="text-primary hover:text-teal-800 font-medium">
                        Sign in here
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
