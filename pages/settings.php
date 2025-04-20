<?php
require_once __DIR__ . '/../config.php';
requireLogin();

// Get current user's role
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userRole === 'admin') {
        // Admin-only settings
        if (isset($_POST['site_title'])) {
            // Check if settings exist for this user
            $stmt = $db->prepare("SELECT id FROM site_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                // Update existing settings
                $stmt = $db->prepare("UPDATE site_settings SET site_title = ?, favicon_url = ? WHERE user_id = ?");
                $stmt->execute([$_POST['site_title'], $_POST['favicon_url'], $_SESSION['user_id']]);
            } else {
                // Insert new settings
                $stmt = $db->prepare("INSERT INTO site_settings (user_id, site_title, favicon_url) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $_POST['site_title'], $_POST['favicon_url']]);
            }
        }
    }
    
    // Update user-specific settings
    if (isset($_POST['ad_url'], $_POST['domains'])) {
        $stmt = $db->prepare("SELECT id FROM video_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Update existing settings
            $stmt = $db->prepare("UPDATE video_settings SET ad_url = ?, domains = ? WHERE user_id = ?");
            $stmt->execute([$_POST['ad_url'], $_POST['domains'], $_SESSION['user_id']]);
        } else {
            // Insert new settings
            $stmt = $db->prepare("INSERT INTO video_settings (user_id, ad_url, domains) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $_POST['ad_url'], $_POST['domains']]);
        }
    }
    
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Settings updated successfully'];
    header("Location: /settings");
    exit;
}

// Get current settings
$stmt = $db->prepare("SELECT * FROM site_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist yet, use defaults
if (!$settings) {
    $settings = [
        'site_title' => 'Video Platform',
        'favicon_url' => '/favicon.ico'
    ];
}

// Get user-specific settings
$stmt = $db->prepare("SELECT * FROM video_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$userSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user settings exist yet, use defaults
if (!$userSettings) {
    $userSettings = [
        'ad_url' => '',
        'domains' => ''
    ];
}

// Get flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<?php require_once __DIR__ . '/../header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
            </div>
        </div>

        <?php if ($flash_message): ?>
            <div id="flash-message" class="<?php echo $flash_message['type'] === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($flash_message['text']); ?>
            </div>
            <script>
                setTimeout(() => {
                    const flashMessage = document.getElementById('flash-message');
                    if (flashMessage) {
                        flashMessage.style.transition = 'opacity 0.5s ease-out';
                        flashMessage.style.opacity = '0';
                        setTimeout(() => {
                            flashMessage.remove();
                        }, 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="space-y-6">
                <?php if ($userRole === 'admin'): ?>
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Site Settings (Admin Only)</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="site_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Site Title
                                </label>
                                <input type="text" 
                                       id="site_title" 
                                       name="site_title" 
                                       class="form-input w-full"
                                       style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                                       placeholder="Enter site title"
                                       value="<?php echo htmlspecialchars($settings['site_title']); ?>">
                                <p class="mt-1 text-sm text-gray-500">This will be displayed in the browser tab and header</p>
                            </div>

                            <div>
                                <label for="favicon_url" class="block text-sm font-medium text-gray-700 mb-2">
                                    Favicon URL
                                </label>
                                <input type="url" 
                                       id="favicon_url" 
                                       name="favicon_url" 
                                       class="form-input w-full"
                                       style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                                       value="<?php echo htmlspecialchars($settings['favicon_url']); ?>"
                                       placeholder="https://example.com/favicon.ico">
                                <?php if (!empty($settings['favicon_url'])): ?>
                                    <div class="mt-2 flex items-center space-x-2">
                                        <span class="text-sm text-gray-500">Current favicon:</span>
                                        <img src="<?php echo htmlspecialchars($settings['favicon_url']); ?>" 
                                             alt="Current favicon" 
                                             class="w-4 h-4">
                                    </div>
                                <?php endif; ?>
                                <p class="mt-1 text-sm text-gray-500">Enter the URL of your favicon (recommended size: 32x32 pixels)</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">User Settings</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="ad_url" class="block text-sm font-medium text-gray-700 mb-2">
                                Advertisement URL
                            </label>
                            <input type="url" 
                                   id="ad_url" 
                                   name="ad_url" 
                                   class="form-input w-full"
                                   style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                                   value="<?php echo htmlspecialchars($userSettings['ad_url']); ?>"
                                   placeholder="https://example.com/ads">
                            <p class="mt-1 text-sm text-gray-500">Enter the URL where your video advertisements are hosted</p>
                        </div>

                        <div>
                            <label for="domains" class="block text-sm font-medium text-gray-700 mb-2">
                                Allowed Domains
                            </label>
                            <textarea id="domains" 
                                      name="domains" 
                                      rows="3" 
                                      class="form-input w-full"
                                      style="padding-left: 0.5rem !important; padding-right: 0.5rem !important;"
                                      placeholder="example.com&#10;subdomain.example.com"><?php echo htmlspecialchars($userSettings['domains']); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Enter one domain per line. These domains will be allowed to embed your videos.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/dashboard" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
