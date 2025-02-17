<?php
session_start();

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

// Create users table if not exists
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

// Make database connection available globally
$GLOBALS['db_host'] = $db_host;
$GLOBALS['db_name'] = $db_name;
$GLOBALS['db_user'] = $db_user;
$GLOBALS['db_pass'] = $db_pass;

?>
