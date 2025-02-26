<?php
require_once 'config.php';

// Get site settings
$site_settings = null;
$site_title = 'Video Platform'; // Default value
$favicon_url = 'https://i.postimg.cc/8NQsW-CMc/play.png?dl=1';  // Default value

// Initialize user role
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

if (isLoggedIn() && isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM site_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($site_settings) {
            $site_title = $site_settings['site_title'];
            $favicon_url = $site_settings['favicon_url'] ?: '/favicon.ico';
        }
    } catch (PDOException $e) {
        // Silently fail, use defaults
    }
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Also check the REQUEST_URI to handle routed pages
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '?') !== false) {
    $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
}
$request_path = trim($request_uri, '/');

// Determine the current page more accurately
if ($request_path === 'dashboard' || $request_path === '') {
    $current_page = 'dashboard';
} elseif ($request_path === 'settings') {
    $current_page = 'settings';
} elseif (strpos($request_path, 'edit') === 0) {
    $current_page = 'edit';
} elseif (strpos($request_path, 'users') === 0) {
    $current_page = 'users';
} elseif (strpos($request_path, 'user-edit') === 0) {
    $current_page = 'user-edit';
} elseif (strpos($request_path, 'add-user') === 0) {
    $current_page = 'add-user';
}

$page_title = '';

// Set page-specific title
if (isset($video) && $current_page === 'player') {
    $page_title = htmlspecialchars($video['title']);
} else {
    $page_title = match($current_page) {
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'edit' => 'Edit Video',
        'users' => 'User Management',
        'user-edit' => 'Edit User',
        'add-user' => 'Add User',
        default => ''
    };
    $page_title = $page_title ? "$page_title - $site_title" : $site_title;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <?php if (!empty($favicon_url)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_url); ?>">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#111827',
                        dark: '#030712',
                        accent: '#4F46E5'
                    },
                    boxShadow: {
                        'glow': '0 0 20px rgba(139, 92, 246, 0.15)',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .btn-primary {
                @apply bg-primary hover:bg-violet-500 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md hover:shadow-glow transform hover:-translate-y-0.5 transition-all duration-200;
            }
            .btn-secondary {
                @apply bg-secondary hover:bg-gray-800 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md transform hover:-translate-y-0.5 transition-all duration-200;
            }
            .btn-danger {
                @apply bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md transform hover:-translate-y-0.5 transition-all duration-200 cursor-pointer;
            }
            .input-field {
                @apply bg-secondary/80 border border-gray-800 text-gray-100 rounded-xl px-5 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/50 backdrop-blur-sm transition-all duration-200;
            }
            .card {
                @apply bg-secondary/95 backdrop-blur-sm rounded-xl p-6 shadow-lg border border-gray-800/50 hover:shadow-glow transition-all duration-300;
            }
            .nav-link {
                @apply text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-primary/10 transition-all duration-200;
            }
            .gradient-text {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-violet-500 to-indigo-600;
            }
            .icon-btn {
                @apply p-2 rounded-lg hover:bg-gray-700/50 transition-all duration-200 cursor-pointer;
            }
            .icon-copy {
                @apply text-gray-400 hover:text-primary;
            }
            .icon-delete {
                @apply text-gray-400 hover:text-red-500;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: theme('colors.dark');
        }
        ::-webkit-scrollbar-thumb {
            background: theme('colors.primary');
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: theme('colors.accent');
        }

        /* Dark theme specific styles */
        ::selection {
            background: theme('colors.primary');
            color: white;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css">

</head>
<body class="bg-dark text-gray-200 min-h-screen font-['Inter'] antialiased selection:bg-primary selection:text-white">
    <?php if (isLoggedIn()): ?>
        <nav class="bg-slate-900/95 border-b border-slate-800 fixed w-full z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/dashboard" class="text-2xl font-bold text-white flex items-center">
                <?php if (!empty($favicon_url)): ?>
                    <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-8 h-8 mr-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_title); ?>
            </a>
            
            <button class="md:hidden text-white text-2xl focus:outline-none" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="hidden md:flex space-x-4" id="nav-menu">
                <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'text-white bg-primary/10' : ''; ?>" onclick="location.href='/dashboard'">
                <i class="fa-duotone fa-thin fa-grid-2 mr-2"></i>Dashboard
                </button>
                <?php if ($userRole === 'admin'): ?>
                    <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'text-white bg-primary/10' : ''; ?>" onclick="location.href='/users'">
                        <i class="fa-duotone fa-thin fa-users mr-2"></i>Users
                    </button>
                <?php endif; ?>
                <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'text-white bg-primary/10' : ''; ?>" onclick="location.href='/settings'">
                    <i class="fa-duotone fa-thin fa-gear mr-2"></i>Settings
                </button>
                <a href="/logout" class="btn-danger">
                    <i class="fa-duotone fa-thin fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
        
        <div class="md:hidden hidden flex flex-col space-y-2 mt-2 p-4 bg-slate-900/95 border-t border-slate-800" id="mobile-menu">
            <a href="/dashboard" class="block py-2 px-4 text-white hover:bg-primary/10 <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'bg-primary/10' : ''; ?>">
                <i class="fa-duotone fa-thin fa-dashboard mr-2"></i>Dashboard
            </a>
            <?php if ($userRole === 'admin'): ?>
                <a href="/users" class="block py-2 px-4 text-white hover:bg-primary/10 <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'bg-primary/10' : ''; ?>">
                    <i class="fa-duotone fa-thin fa-users mr-2"></i>Users
                </a>
            <?php endif; ?>
            <a href="/settings" class="block py-2 px-4 text-white hover:bg-primary/10 <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'bg-primary/10' : ''; ?>">
                <i class="fa-duotone fa-thin fa-gear mr-2"></i>Settings
            </a>
            <a href="/logout" class="block w-full text-left py-2 px-4 text-white hover:bg-red-700">
                <i class="fa-duotone fa-thin fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </div>
</nav>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>

    <?php endif; ?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 md:pt-20">
