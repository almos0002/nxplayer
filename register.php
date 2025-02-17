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

<div class="flex items-center justify-center min-h-[80vh]">
    <div class="card w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Create Account</h1>
            <p class="text-gray-400">Join our video proxy platform</p>
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
                       pattern="[a-zA-Z0-9_-]{3,50}"
                       title="Username can only contain letters, numbers, dashes and underscores (3-50 characters)"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                <input type="email" name="email" required
                       class="input-field w-full"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                <input type="password" name="password" required
                       class="input-field w-full"
                       minlength="6"
                       title="Password must be at least 6 characters long">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" required
                       class="input-field w-full"
                       minlength="6">
            </div>

            <button type="submit" class="btn-primary w-full">
                Create Account
            </button>

            <div class="text-center text-gray-400 text-sm">
                Already have an account? 
                <a href="/login" class="text-primary hover:text-orange-500 font-medium">
                    Sign in here
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'footer.php'; ?>
