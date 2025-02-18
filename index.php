<?php
require_once 'config.php';
require_once 'router.php';

// Public routes (no auth required)
$public_routes = ['/login.php', '/register.php', '/login', '/register', '/:slug'];

// Protected route prefixes (require auth)
$protected_routes = ['/dashboard', '/settings', '/edit', '/api.php', '/users', '/user-edit', '/add-user'];

// Check if the current route requires authentication
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requires_auth = false;
foreach ($protected_routes as $route) {
    if (strpos($current_path, $route) === 0) {
        $requires_auth = true;
        break;
    }
}

if ($requires_auth && !isLoggedIn()) {
    header('Location: /login');
    exit;
}

// Get database connection
global $db;

$router = new Router();

// Define routes
$router->get('/', function() {
    if (!isLoggedIn()) {
        header('Location: /login');
    } else {
        header('Location: /dashboard');
    }
    exit;
});

// Auth routes
$router->get('/login', function() {
    global $db;
    require 'login.php';
});

$router->post('/login', function() {
    global $db;
    require 'login.php';
});

$router->get('/register', function() {
    global $db;
    require 'register.php';
});

$router->post('/register', function() {
    global $db;
    require 'register.php';
});

$router->get('/logout', function() {
    global $db;
    require 'logout.php';
});

$router->post('/logout', function() {
    global $db;
    require 'logout.php';
});

// Protected routes (require auth)
$router->get('/dashboard', function() {
    global $db;
    requireLogin();
    require 'pages/dashboard.php';
});

$router->post('/dashboard', function() {
    global $db;
    requireLogin();
    require 'pages/dashboard.php';
});

$router->get('/settings', function() {
    global $db;
    requireLogin();
    require 'pages/settings.php';
});

$router->post('/settings', function() {
    global $db;
    requireLogin();
    require 'pages/settings.php';
});

$router->get('/edit', function() {
    global $db;
    requireLogin();
    require 'pages/edit.php';
});

$router->post('/edit', function() {
    global $db;
    requireLogin();
    require 'pages/edit.php';
});

$router->get('/users', function() {
    global $db;
    requireLogin();
    require 'pages/users.php';
});

$router->post('/users', function() {
    global $db;
    requireLogin();
    require 'pages/users.php';
});

$router->get('/user-edit', function() {
    global $db;
    requireLogin();
    require 'pages/user-edit.php';
});

$router->post('/user-edit', function() {
    global $db;
    requireLogin();
    require 'pages/user-edit.php';
});

$router->get('/add-user', function() {
    global $db;
    requireLogin();
    require 'pages/add-user.php';
});

$router->post('/add-user', function() {
    global $db;
    requireLogin();
    require 'pages/add-user.php';
});

// Video player route
$router->get('/:slug', function($params) {
    global $db;
    
    $slug = $params['slug'];
    $stmt = $db->prepare("SELECT * FROM videos WHERE slug = ?");
    $stmt->execute([$slug]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        header('HTTP/1.0 404 Not Found');
        echo "Video not found";
        exit;
    }
    
    require 'pages/player.php';
});

// API routes (require auth)
$router->get('/api.php', function() {
    global $db;
    requireLogin();
    require 'api.php';
});

$router->post('/api.php', function() {
    global $db;
    requireLogin();
    require 'api.php';
});

// 404 handler
$router->notFound(function() {
    header('HTTP/1.0 404 Not Found');
    echo "404 - Page Not Found";
});

// Run the router
$router->run();
?>
