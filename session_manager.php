<?php
/**
 * Session Manager
 * Handles session configuration, security, and expiration settings
 */

// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');

// Default session lifetime - 12 hours in seconds
$session_lifetime = 12 * 3600;

// Check if there's an active session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params($session_lifetime, '/', '', isset($_SERVER['HTTPS']), true);
    session_start();
}

// Session expiration check
function checkSessionExpiration() {
    // Don't check for remember_me users
    if (isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === true) {
        return;
    }
    
    // For regular sessions, check if they've expired
    if (isset($_SESSION['last_activity'])) {
        $session_lifetime = 12 * 3600; // 12 hours in seconds
        $current_time = time();
        
        // If session is older than allowed lifetime
        if (($current_time - $_SESSION['last_activity']) > $session_lifetime) {
            // Session expired, destroy it
            session_unset();
            session_destroy();
            
            // Redirect to login with expired message
            header("Location: /login?expired=1");
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Regenerate session ID periodically to prevent session fixation
function regenerateSessionId() {
    $regeneration_time = 30 * 60; // 30 minutes in seconds
    
    if (isset($_SESSION['last_regeneration'])) {
        $current_time = time();
        
        if (($current_time - $_SESSION['last_regeneration']) > $regeneration_time) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $current_time;
        }
    } else {
        $_SESSION['last_regeneration'] = time();
    }
}

// Run the session security checks
checkSessionExpiration();
regenerateSessionId();
