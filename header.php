<?php
require_once 'config.php';

// Get site settings
$site_settings = null;
$site_title = 'Video Platform'; // Default value
$favicon_url = '/favicon.ico';  // Default value

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
$page_title = '';

// Set page-specific title
if (isset($video) && $current_page === 'player') {
    $page_title = htmlspecialchars($video['title']);
} else {
    $page_title = match($current_page) {
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'edit' => 'Edit Video',
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-dark text-gray-200 min-h-screen font-['Inter'] antialiased selection:bg-primary selection:text-white">
    <?php if (isLoggedIn()): ?>
    <nav class="bg-secondary border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/dashboard" class="text-primary font-bold text-xl"><?php echo htmlspecialchars($site_title); ?></a>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="/dashboard" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false ? 'text-white bg-primary/10' : ''; ?>">
                                <i class="fas fa-video mr-2"></i>Dashboard
                            </a>
                            <?php
                            // Check if user is admin
                            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $userRole = $stmt->fetchColumn();
                            
                            if ($userRole === 'admin'): 
                            ?>
                                <a href="/users" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false || strpos($_SERVER['REQUEST_URI'], '/user-edit') !== false || strpos($_SERVER['REQUEST_URI'], '/add-user') !== false ? 'text-white bg-primary/10' : ''; ?>">
                                    <i class="fas fa-users mr-2"></i>Users
                                </a>
                            <?php endif; ?>
                            <a href="/settings" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false ? 'text-white bg-primary/10' : ''; ?>">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                            <a href="/logout" class="nav-link">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-300">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <form action="/logout" method="POST" class="m-0">
                        <button type="submit" class="btn-danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
