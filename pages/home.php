<?php
require_once __DIR__ . '/../config.php';

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
    <title><?php echo htmlspecialchars($page_title); ?> - Gdrive Proxy Player</title>

    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php if (!empty($favicon_url)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_url); ?>">
    <?php endif; ?>
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        .gradient-text {
            background: linear-gradient(to right, #0d9488, #0f766e, #115e59);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradient 8s ease infinite;
            background-size: 200% auto;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
            border-radius: 16px;
        }
        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            border-color: rgba(13, 148, 136, 0.3);
        }
        
        .feature-icon {
            animation: float 3s ease-in-out infinite;
        }
        
        .btn-glow {
            position: relative;
            z-index: 1;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-glow:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(13, 148, 136, 0.5), rgba(15, 118, 110, 0.5));
            z-index: -1;
            transform: scaleX(0);
            transform-origin: 0 50%;
            transition: transform 0.5s ease-out;
        }
        
        .btn-primary {
            background-color: var(--color-primary, #0d9488);
            border: none;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .btn-primary:hover {
            background-color: #0f766e;
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            color: #374151;
            border: 1px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(243, 244, 246, 0.8);
            border-color: rgba(209, 213, 219, 0.8);
        }
        
        .floating-image {
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
    </style>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        
        .gradient-text {
            background: linear-gradient(to right, #0d9488, #0f766e, #115e59);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gradient 8s ease infinite;
            background-size: 200% auto;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .hero-section {
            background: radial-gradient(circle at top right, rgba(13, 148, 136, 0.1), transparent 70%),
                        radial-gradient(circle at bottom left, rgba(15, 118, 110, 0.1), transparent 70%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.05) 0%, transparent 70%);
            z-index: 0;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
            border-radius: 16px;
        }
        
        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            border-color: rgba(13, 148, 136, 0.3);
        }
        
        .feature-icon {
            animation: float 3s ease-in-out infinite;
        }
        
        .btn-glow {
            position: relative;
            z-index: 1;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-glow:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(13, 148, 136, 0.5), rgba(15, 118, 110, 0.5));
            z-index: -1;
            transform: scaleX(0);
            transform-origin: 0 50%;
            transition: transform 0.5s ease-out;
        }
        
        .btn-primary {
            background-color: var(--color-primary, #0d9488);
            border: none;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        
        .btn-primary:hover {
            background-color: #0f766e;
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            color: #374151;
            border: 1px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(243, 244, 246, 0.8);
            border-color: rgba(209, 213, 219, 0.8);
        }
        
        .floating-image {
            animation: float 6s ease-in-out infinite;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 overflow-x-hidden">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm fixed w-full z-[9999] transition-all duration-300">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <a href="/" class="text-2xl font-bold text-gray-800 flex items-center hover:text-primary transition-colors duration-300">
                <?php if (!empty($favicon_url)): ?>
                    <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-8 h-8 mr-2 animate-pulse">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_title); ?>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="md:hidden text-gray-700 text-2xl focus:outline-none transition-transform duration-300 hover:scale-110" id="menu-toggle">
                <i class="fa-duotone fa-thin fa-bars-staggered"></i>
            </button>

            <!-- Navigation Links -->
            <div id="menu" class="hidden lg:flex flex-col lg:flex-row items-center space-y-4 lg:space-y-0 lg:space-x-6 absolute lg:static top-16 left-0 w-full lg:w-auto bg-white/90 backdrop-blur-md lg:bg-transparent py-4 lg:py-0 px-6 lg:px-0 shadow-lg lg:shadow-none">
                <?php if (isLoggedIn()): ?>
                    <a href="/dashboard" class="btn-primary btn-glow py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-sm transition duration-300 ease-in-out">Go to Dashboard</a>
                <?php else: ?>
                    <a href="/login" class="btn-secondary py-2.5 px-5 rounded-lg text-base font-semibold shadow-sm transition duration-300 ease-in-out">Login</a>
                    <a href="/register" class="btn-primary btn-glow py-2.5 px-5 rounded-lg text-base font-semibold text-white shadow-sm transition duration-300 ease-in-out">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('menu').classList.toggle('hidden');
    });
    
    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('nav');
        if (window.scrollY > 10) {
            nav.classList.add('shadow-md');
            nav.classList.remove('shadow-sm');
        } else {
            nav.classList.remove('shadow-md');
            nav.classList.add('shadow-sm');
        }
    });
</script>


    <!-- Hero Section -->
    <section class="pt-24 pb-12 hero-section min-h-[90vh] flex items-center">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row items-center justify-between">
                <div class="lg:w-1/2 mb-12 lg:mb-0" style="animation: fadeIn 1s ease-out;">
                    <h1 class="text-5xl md:text-6xl font-bold text-gray-800 mb-6 leading-tight">
                        <span class="gradient-text">Gdrive Proxy Player</span>
                        <br>With Video Management
                    </h1>
                    <p class="text-xl text-gray-600 mb-8" style="animation: fadeIn 1.2s ease-out;">
                        Streamline your gdrive video content and management with our platform. 
                        Upload, manage, and share your videos with ease.
                    </p>
                    <p class="text-xl text-gray-600 mb-8" style="animation: fadeIn 1.4s ease-out;">
                        For DMCA Takedown Mail to <span class="font-bold">report@nxshare.top</span>
                    </p>
                    <div class="flex space-x-4" style="animation: fadeIn 1.6s ease-out;">
                        <?php if (!isLoggedIn()): ?>
                            <a href="/register" class="btn-primary btn-glow py-3 px-8 rounded-lg text-base font-semibold text-white shadow-md transition duration-300 ease-in-out">
                                Start Free
                                <i class="fa-duotone fa-thin fa-arrow-right ml-2"></i>
                            </a>
                            <a href="#features" class="btn-secondary py-3 px-8 rounded-lg text-base font-semibold shadow-sm transition duration-300 ease-in-out">
                                Learn More
                            </a>
                        <?php else: ?>
                            <a href="/dashboard" class="btn-primary btn-glow py-3 px-8 rounded-lg text-base font-semibold text-white shadow-md transition duration-300 ease-in-out">
                                Go to Dashboard
                                <i class="fa-duotone fa-thin fa-arrow-right ml-2"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lg:w-1/2" style="animation: fadeIn 1.8s ease-out;">
                    <div class="relative" style="animation: float 6s ease-in-out infinite;">
                        <div class="absolute inset-0 bg-gradient-to-r from-teal-500/20 to-emerald-500/20 rounded-2xl transform rotate-3 scale-105 blur-xl"></div>
                        <div class="absolute -inset-1 bg-gradient-to-r from-teal-500/10 to-emerald-500/10 rounded-2xl transform -rotate-2 scale-105 blur-xl"></div>
                        <img src="https://i.postimg.cc/x8vPh99C/scrnli-C6-GBVFqq8-Sb-Z0m.png" 
                             alt="Platform Preview" 
                             class="rounded-2xl shadow-2xl relative z-10 floating-image">
                        <div class="absolute -bottom-4 -right-4 w-20 h-20 bg-gradient-to-r from-teal-500 to-emerald-500 rounded-full blur-xl opacity-40"></div>
                        <div class="absolute -top-4 -left-4 w-16 h-16 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full blur-xl opacity-40"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-1/4 left-10 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-10 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500/5 rounded-full blur-3xl -z-10"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-emerald-500/5 rounded-full blur-3xl -z-10"></div>
        <div class="container mx-auto px-4">
            <h2 class="text-4xl md:text-5xl font-bold text-center mb-16" style="animation: fadeIn 0.8s ease-out;">
                <span class="gradient-text">Powerful Features</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 relative">
                <!-- Feature 1 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-teal-500/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                        <i class="fa-duotone fa-thin fa-cloud-arrow-up text-3xl text-teal-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Easy Video Upload</h3>
                    <p class="text-gray-600">
                        Just enter your google drive video id and get a video embed url with customizable options.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-emerald-500/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                    <i class="fa-duotone fa-thin fa-globe-pointer text-3xl text-emerald-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Domain Restrictions</h3>
                    <p class="text-gray-600">
                        Restrict access to your videos based on domain names.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-teal-600/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                        <i class="fa-duotone fa-thin fa-chart-line text-3xl text-teal-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Analytics Dashboard</h3>
                    <p class="text-gray-600">
                        Simple analytics dashboard to display the details of your account.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-teal-700/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                        <i class="fa-duotone fa-thin fa-audio-description text-3xl text-teal-700"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Place your Ad</h3>
                    <p class="text-gray-600">
                        Place your ad on your videos and earn money.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-emerald-600/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                        <i class="fa-duotone fa-thin fa-share-from-square text-3xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Easy Sharing</h3>
                    <p class="text-gray-600">
                        Share your videos easily or Embed them on your website.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card p-8">
                    <div class="w-16 h-16 bg-emerald-700/10 rounded-xl flex items-center justify-center mb-6 feature-icon">
                        <i class="fa-duotone fa-thin fa-rabbit-running text-3xl text-emerald-700"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Fast Performance</h3>
                    <p class="text-gray-600">
                        Fast performance and low latency for smooth video streaming.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-teal-50 to-emerald-50 -z-10"></div>
        <div class="absolute top-0 left-1/4 w-72 h-72 bg-teal-500/5 rounded-full blur-3xl -z-10"></div>
        <div class="absolute bottom-0 right-1/4 w-72 h-72 bg-emerald-500/5 rounded-full blur-3xl -z-10"></div>
        
        <div class="container mx-auto px-4 text-center">
            <div class="glass-card py-16 px-8 max-w-4xl mx-auto">
            <h2 class="text-4xl md:text-5xl font-bold mb-6" style="animation: fadeIn 0.8s ease-out;">
                <span class="gradient-text">Ready to Get Started?</span>
            </h2>
            <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto" style="animation: fadeIn 1s ease-out;">
                Join and use our <?php echo htmlspecialchars($site_title); ?> website to stream gdrive videos. Based on Gdplayer Api.
            </p>
            <?php if (!isLoggedIn()): ?>
                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6" style="animation: fadeIn 1.2s ease-out;">
                    <a href="/register" class="btn-primary btn-glow py-3 px-8 rounded-lg text-base font-semibold text-white shadow-md transition duration-300 ease-in-out">
                        Create Free Account
                    </a>
                    <a href="/login" class="btn-secondary py-3 px-8 rounded-lg text-base font-semibold shadow-sm transition duration-300 ease-in-out">
                        Sign In
                    </a>
                </div>
            <?php else: ?>
                <div style="animation: fadeIn 1.2s ease-out;">
                    <a href="/dashboard" class="btn-primary btn-glow py-3 px-8 rounded-lg text-base font-semibold text-white shadow-md transition duration-300 ease-in-out">Go to Dashboard</a>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 py-16 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-500 via-emerald-500 to-teal-600"></div>
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-8 md:mb-0">
                <a href="/" class="text-2xl font-bold text-gray-800 flex items-center hover:text-primary transition-colors duration-300">
                        <?php if (!empty($favicon_url)): ?>
                            <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="" class="w-10 h-10 mr-3">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($site_title); ?>
                    </a>
                    <p class="text-gray-500 mt-3">Advanced Gdrive Proxy Player</p>
                </div>
                <div class="flex space-x-8">
                    <a href="#" class="text-gray-400 hover:text-teal-500 transition-colors duration-300 transform hover:scale-110">
                        <i class="fab fa-twitter text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-900 transition-colors duration-300 transform hover:scale-110">
                        <i class="fab fa-github text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-teal-700 transition-colors duration-300 transform hover:scale-110">
                        <i class="fab fa-linkedin text-2xl"></i>
                    </a>
                </div>
            </div>
            <div class="border-t border-gray-100 mt-12 pt-10 text-center text-gray-500">
                <p class="text-sm">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
