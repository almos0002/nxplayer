<?php
require_once __DIR__ . '/../config.php';

$site_settings = null;
$site_title = 'Video Platform'; // Default value
$favicon_url = 'https://i.postimg.cc/8NQsW-CMc/play.png?dl=1';  // Default value

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
    <title>Proxy Player - Video Management Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (!empty($favicon_url)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_url); ?>">
    <?php endif; ?>
    <style>
        body {
            background-color: #0f172a;
            font-family: 'Inter', sans-serif;
            color: #e2e8f0;
        }
        .gradient-text {
            background: linear-gradient(45deg, #60a5fa, #3b82f6, #2563eb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(71, 85, 105, 0.2);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
        }
        .hero-gradient {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navigation -->
    <nav class="bg-slate-900/95 border-b border-slate-800 fixed w-full z-[9999]">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <a href="/" class="text-2xl font-bold text-white flex items-center">
                <?php if (!empty($favicon_url)): ?>
                    <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-8 h-8 mr-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_title); ?>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-white text-2xl focus:outline-none" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation Links -->
            <div id="menu" class="hidden lg:flex flex-col lg:flex-row items-center space-y-4 lg:space-y-0 lg:space-x-4 absolute lg:static top-16 left-0 w-full lg:w-auto bg-slate-900 lg:bg-transparent py-4 lg:py-0 px-6 lg:px-0 shadow-lg lg:shadow-none">
                <?php if (isLoggedIn()): ?>
                    <a href="/dashboard" class="btn-primary bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">Go to Dashboard</a>
                <?php else: ?>
                    <a href="/login" class="btn bg-gray-800 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-semibold py-2.5 px-5 rounded-lg shadow-lg transition duration-200 ease-in-out">Login</a>
                    <a href="/register" class="btn bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('menu').classList.toggle('hidden');
    });
</script>


    <!-- Hero Section -->
    <section class="pt-24 pb-12 hero-gradient">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row items-center justify-between">
                <div class="lg:w-1/2 mb-12 lg:mb-0">
                    <h1 class="text-5xl font-bold mb-6">
                        <span class="gradient-text">Advanced Video</span>
                        <br>Management Platform
                    </h1>
                    <p class="text-xl text-gray-300 mb-8">
                        Streamline your video content management with our powerful platform. 
                        Host, manage, and share your videos with ease.
                    </p>
                    <div class="flex space-x-4">
                        <?php if (!isLoggedIn()): ?>
                            <a href="/register" class="btn bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">
                                Start Free
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            <a href="#features" class="btn bg-gray-800 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-semibold py-2.5 px-5 rounded-lg shadow-lg transition duration-200 ease-in-out">
                                Learn More
                            </a>
                        <?php else: ?>
                            <a href="/dashboard" class="btn-primary bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">
                                Go to Dashboard
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lg:w-1/2">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500 rounded-lg transform rotate-3 scale-105 opacity-10"></div>
                        <img src="https://placehold.co/600x400/1e3a8a/ffffff?text=Video+Management" 
                             alt="Platform Preview" 
                             class="rounded-lg shadow-2xl relative z-10">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-12">
                <span class="gradient-text">Powerful Features</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-cloud-upload-alt text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Easy Video Upload</h3>
                    <p class="text-gray-400">
                        Upload your videos with a simple interface. Support for multiple formats and high-quality streaming.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-users-cog text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">User Management</h3>
                    <p class="text-gray-400">
                        Comprehensive user management with roles and permissions. Control who can access your content.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-2xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Analytics Dashboard</h3>
                    <p class="text-gray-400">
                        Track video performance and user engagement with detailed analytics and insights.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-2xl text-red-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Secure Storage</h3>
                    <p class="text-gray-400">
                        Your videos are stored securely with encryption and regular backups for peace of mind.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-share-alt text-2xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Easy Sharing</h3>
                    <p class="text-gray-400">
                        Share your videos easily with customizable embed options and direct links.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card rounded-lg p-6">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-cogs text-2xl text-indigo-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Customization</h3>
                    <p class="text-gray-400">
                        Customize your player appearance and settings to match your brand identity.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-br from-slate-900 to-slate-800">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Get Started?</h2>
            <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
                Join thousands of users who trust <?php echo htmlspecialchars($site_title); ?> for their video management needs.
            </p>
            <?php if (!isLoggedIn()): ?>
                <div class="flex justify-center space-x-4">
                    <a href="/register" class="btn bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">
                        Create Free Account
                    </a>
                    <a href="/login" class="btn bg-gray-800 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 text-white font-semibold py-2.5 px-5 rounded-lg shadow-lg transition duration-200 ease-in-out">
                        Sign In
                    </a>
                </div>
            <?php else: ?>
                <a href="/dashboard" class="btn-primary bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-4 focus:ring-violet-300 focus:ring-opacity-50 focus:outline-none focus:ring-offset-2 focus:ring-offset-violet-200 py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-lg transition duration-200 ease-in-out">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-8 md:mb-0">
                <a href="/" class="text-2xl font-bold text-white flex items-center">
                        <?php if (!empty($favicon_url)): ?>
                            <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-8 h-8 mr-2">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($site_title); ?>
                    </a>
                    <p class="text-gray-400 mt-2">Advanced Video Management Platform</p>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                </div>
            </div>
            <div class="border-t border-slate-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
