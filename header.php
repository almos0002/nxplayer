<?php
require_once 'config.php';

// Get site settings
$site_settings = null;
$site_title = 'Video Platform'; // Default value
$favicon_url = 'https://i.postimg.cc/8NQsW-CMc/play.png?dl=1';  // Default value

// Initialize user role
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// First try to get admin settings
try {
    $stmt = $db->prepare("SELECT u.id, ss.* FROM users u 
                         LEFT JOIN site_settings ss ON u.id = ss.user_id 
                         WHERE u.role = 'admin' 
                         ORDER BY ss.id DESC LIMIT 1");
    $stmt->execute();
    $site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($site_settings) {
        $site_title = $site_settings['site_title'];
        $favicon_url = $site_settings['favicon_url'] ?: '/favicon.ico';
    }
} catch (PDOException $e) {
    // Silently fail, use defaults
}

// If logged in user is admin, override with their settings
if (isLoggedIn() && isset($_SESSION['user_id']) && $userRole === 'admin') {
    try {
        $stmt = $db->prepare("SELECT * FROM site_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_settings) {
            $site_title = $user_settings['site_title'];
            $favicon_url = $user_settings['favicon_url'] ?: '/favicon.ico';
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
                        primary: '#0F766E',   /* Teal-700 */
                        secondary: '#E5E7EB', /* Gray-200 */
                        dark: '#1F2937',     /* Gray-800 */
                        accent: '#EC4899',    /* Pink-500 */
                        neutral: '#F9FAFB',   /* Gray-50 */
                        'surface': '#FFFFFF',
                        'surface-hover': '#F3F4F6'
                    },
                    boxShadow: {
                        'subtle': '0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03)',
                        'elevated': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025)',
                        'card': '0 2px 4px rgba(0, 0, 0, 0.02), 0 1px 10px rgba(0, 0, 0, 0.03)'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif']
                    },
                    borderRadius: {
                        'xl': '0.75rem',
                        '2xl': '1rem'
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            /* Button Styles */
            .btn-primary {
                @apply bg-primary hover:bg-teal-800 text-white font-medium py-2.5 px-6 rounded-xl shadow-subtle hover:shadow-elevated transition-all duration-200;
            }
            .btn-secondary {
                @apply bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 font-medium py-2.5 px-6 rounded-xl shadow-subtle hover:shadow-elevated transition-all duration-200;
            }
            .btn-danger {
                @apply bg-red-500 hover:bg-red-600 text-white font-medium py-2.5 px-6 rounded-xl shadow-subtle hover:shadow-elevated transition-all duration-200;
            }
            .btn-outline {
                @apply border border-gray-200 hover:border-primary hover:text-primary text-gray-600 font-medium py-2.5 px-6 rounded-xl transition-all duration-200;
            }
            
            /* Navigation */
            .nav-link {
                @apply text-gray-600 hover:text-primary font-medium py-2 px-4 rounded-lg transition-all duration-200;
            }
            
            /* Form Elements */
            .form-input {
                @apply bg-white border border-gray-200 text-gray-700 rounded-xl px-8 py-3 focus:outline-none focus:ring-1 focus:ring-primary/30 focus:border-primary shadow-sm transition-all duration-200 w-full;
            }
            
            /* Card Components */
            .card {
                @apply bg-white border border-gray-100 rounded-xl p-6 shadow-card hover:shadow-elevated transition-all duration-300;
            }
            
            /* Stat Cards */
            .stat-card {
                @apply bg-white border border-gray-100 rounded-xl p-6 shadow-card transition-all duration-300;
            }
            .stat-icon {
                @apply w-12 h-12 flex items-center justify-center rounded-full text-white text-xl;
            }
            
            /* Interactive Elements */
            .hover-lift {
                @apply hover:-translate-y-1 hover:shadow-elevated;
            }
            .icon-btn {
                @apply p-1.5 rounded-full text-gray-500 border border-gray-200 hover:text-primary hover:bg-gray-100 transition-all duration-200 w-7 h-7 flex items-center justify-center;
            }
            .icon-copy {
                @apply text-gray-400 hover:text-primary;
            }
            .icon-delete {
                @apply text-gray-400 hover:text-red-500;
            }
            
            /* Text Utilities */
            .line-clamp-2 {
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }
            
            /* Shadow Variations */
            .shadow-elevated {
                @apply shadow-lg;
            }
            .gradient-text {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-teal-600 to-teal-400;
            }
            .stat-card {
                @apply bg-white border border-gray-100 rounded-xl p-5 shadow-card hover:shadow-elevated transition-all duration-300;
            }
            .stat-icon {
                @apply p-3 rounded-xl text-white text-xl;
            }
            .icon-btn {
                @apply p-2 rounded-lg hover:bg-gray-50 transition-all duration-200 cursor-pointer;
            }
            .icon-copy {
                @apply text-gray-400 hover:text-primary;
            }
            .icon-delete {
                @apply text-gray-400 hover:text-red-500;
            }
            .page-title {
                @apply text-3xl font-bold text-gray-800 mb-6;
            }
            .section-title {
                @apply text-xl font-semibold text-gray-700 mb-4;
            }
            .data-table {
                @apply w-full border-collapse;
            }
            .data-table th {
                @apply text-left py-3 px-4 bg-gray-50 text-gray-600 font-medium text-sm border-b border-gray-200;
            }
            .data-table td {
                @apply py-3 px-4 border-b border-gray-100 text-gray-700;
            }
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: theme('colors.gray.50');
        }
        ::-webkit-scrollbar-thumb {
            background: theme('colors.gray.200');
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: theme('colors.primary');
        }

        /* Light theme specific styles */
        ::selection {
            background: theme('colors.primary');
            color: white;
        }
        
        /* Global styles */
        body {
            @apply text-gray-700 bg-gray-50;
        }
        
        /* Animation utilities */
        .hover-lift {
            @apply transition-transform duration-200 hover:-translate-y-1;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css">

</head>
<body class="bg-gray-50 text-gray-700 min-h-screen font-sans antialiased selection:bg-primary selection:text-white">
    <?php if (isLoggedIn()): ?>
        <nav class="bg-white border-b border-gray-100 shadow-subtle fixed w-full z-10 backdrop-blur-sm bg-white/95">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/dashboard" class="text-2xl font-bold text-primary flex items-center">
                <?php if (!empty($favicon_url)): ?>
                    <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-8 h-8 mr-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_title ?? 'Video Platform'); ?>
            </a>
            
            <button class="md:hidden text-gray-800 text-2xl focus:outline-none" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="hidden md:flex space-x-4" id="nav-menu">
                <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'text-primary bg-teal-50 font-semibold' : 'text-gray-600 hover:text-primary'; ?>" onclick="location.href='/dashboard'">
                <i class="fa-duotone fa-thin fa-grid-2 mr-2"></i>Dashboard
                </button>
                <?php if ($userRole === 'admin'): ?>
                    <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'text-primary bg-teal-50 font-semibold' : 'text-gray-600 hover:text-primary'; ?>" onclick="location.href='/users'">
                        <i class="fa-duotone fa-thin fa-users mr-2"></i>Users
                    </button>
                <?php endif; ?>
                <button type="button" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'text-primary bg-teal-50 font-semibold' : 'text-gray-600 hover:text-primary'; ?>" onclick="location.href='/settings'">
                    <i class="fa-duotone fa-thin fa-gear mr-2"></i>Settings
                </button>
                <a href="/logout" class="btn-danger">
                    <i class="fa-duotone fa-thin fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
        
        <div class="md:hidden hidden flex flex-col space-y-2 mt-2 p-4 bg-white border-t border-gray-100 shadow-subtle backdrop-blur-sm bg-white/95" id="mobile-menu">
            <a href="/dashboard" class="block py-2 px-4 text-gray-600 hover:bg-teal-50 hover:text-primary <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'bg-teal-50 text-primary font-medium' : ''; ?>">
                <i class="fa-duotone fa-thin fa-dashboard mr-2"></i>Dashboard
            </a>
            <?php if ($userRole === 'admin'): ?>
                <a href="/users" class="block py-2 px-4 text-gray-600 hover:bg-teal-50 hover:text-primary <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'bg-teal-50 text-primary font-medium' : ''; ?>">
                    <i class="fa-duotone fa-thin fa-users mr-2"></i>Users
                </a>
            <?php endif; ?>
            <a href="/settings" class="block py-2 px-4 text-gray-600 hover:bg-teal-50 hover:text-primary <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'bg-teal-50 text-primary font-medium' : ''; ?>">
                <i class="fa-duotone fa-thin fa-gear mr-2"></i>Settings
            </a>
            <a href="/logout" class="block w-full text-left py-2 px-4 text-gray-700 hover:bg-red-50 hover:text-red-600">
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
