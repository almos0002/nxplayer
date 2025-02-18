<?php
require_once 'config.php';

// Clear remember-me cookies if they exist
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
    // Delete the token from database
    try {
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$_COOKIE['remember_user'], $_COOKIE['remember_token']]);
    } catch (PDOException $e) {
        // Silently fail, continue with logout
    }
    
    // Clear cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
}

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: /login");
exit;
?>
