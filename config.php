<?php
// Include session manager for enhanced security
require_once 'session_manager.php';

// Database configuration
$db_host = 'localhost';
$db_name = 'proxyplayer';
$db_user = 'root';
$db_pass = '';

try {
    $GLOBALS['db'] = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $GLOBALS['db']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db = $GLOBALS['db']; // Make it available in the current scope as well
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add role column if it doesn't exist
try {
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') DEFAULT 'user'");
} catch(PDOException $e) {
    // If the column already exists, ignore the error
}

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Create remember_tokens table if not exists
$sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token)
)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Create videos table if not exists
$sql = "CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    file_id VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255),
    subtitle TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Create video_settings table if not exists
$sql = "CREATE TABLE IF NOT EXISTS video_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ad_url TEXT,
    domains TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Create site_settings table if not exists
$sql = "CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    site_title VARCHAR(255) NOT NULL DEFAULT 'Video Platform',
    favicon_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Migrate existing data if needed
$sql = "INSERT INTO video_settings (user_id, ad_url, domains) 
        SELECT id, ad_url, domains FROM videos 
        WHERE ad_url IS NOT NULL OR domains IS NOT NULL 
        ORDER BY id DESC LIMIT 1 
        ON DUPLICATE KEY UPDATE 
        ad_url = VALUES(ad_url), 
        domains = VALUES(domains)";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    // Ignore if already migrated
}

// Remove columns from videos table
$sql = "ALTER TABLE videos DROP COLUMN IF EXISTS ad_url, DROP COLUMN IF EXISTS domains";

try {
    $db->exec($sql);
} catch(PDOException $e) {
    die("Column removal failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check if session has expired (only for non-remember-me sessions)
    if (isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === false) {
        // Check if session has been inactive for more than 12 hours
        $session_lifetime = 12 * 3600; // 12 hours in seconds
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
            // Session expired, destroy it
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update the last activity time
    $_SESSION['last_activity'] = time();
    
    global $db;
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() !== false;
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Check if the session existed but was expired (based on last_activity)
        if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
            // Clear any remaining session
            session_unset();
            session_destroy();
            
            // Redirect with session expired parameter
            header('Location: /login.php?expired=1');
        } else {
            // Regular not logged in
            header('Location: /login.php');
        }
        exit;
    }
}

// Make database connection available globally
$GLOBALS['db_host'] = $db_host;
$GLOBALS['db_name'] = $db_name;
$GLOBALS['db_user'] = $db_user;
$GLOBALS['db_pass'] = $db_pass;

?>
