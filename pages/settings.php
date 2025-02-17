<?php
require_once 'config.php';
requireLogin();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_url = $_POST['ad_url'] ?? '';
    $domains = $_POST['domains'] ?? '';
    $site_title = $_POST['site_title'] ?? '';
    $favicon_url = $_POST['favicon_url'] ?? '';

    try {
        // Start transaction
        $db->beginTransaction();

        // Update video settings
        $stmt = $db->prepare("SELECT COUNT(*) FROM video_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hasVideoSettings = $stmt->fetchColumn() > 0;

        if ($hasVideoSettings) {
            $stmt = $db->prepare("UPDATE video_settings SET ad_url = ?, domains = ? WHERE user_id = ?");
            $stmt->execute([$ad_url, $domains, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO video_settings (user_id, ad_url, domains) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $ad_url, $domains]);
        }

        // Update site settings
        $stmt = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $hasSiteSettings = $stmt->fetchColumn() > 0;

        if ($hasSiteSettings) {
            $stmt = $db->prepare("UPDATE site_settings SET site_title = ?, favicon_url = ? WHERE user_id = ?");
            $stmt->execute([$site_title, $favicon_url, $_SESSION['user_id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO site_settings (user_id, site_title, favicon_url) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $site_title, $favicon_url]);
        }

        // Commit transaction
        $db->commit();

        $_SESSION['flash'] = 'Settings updated successfully';
        $_SESSION['flash_type'] = 'success';
        header('Location: /settings');
        exit;
    } catch (PDOException $e) {
        // Rollback transaction
        $db->rollBack();
        $_SESSION['flash'] = 'Failed to update settings: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
        header('Location: /settings');
        exit;
    }
}

// Get current settings
try {
    // Get video settings
    $stmt = $db->prepare("SELECT * FROM video_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $videoSettings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get site settings
    $stmt = $db->prepare("SELECT * FROM site_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $siteSettings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Merge settings
    $settings = [
        'ad_url' => $videoSettings['ad_url'] ?? '',
        'domains' => $videoSettings['domains'] ?? '',
        'site_title' => $siteSettings['site_title'] ?? 'Video Platform',
        'favicon_url' => $siteSettings['favicon_url'] ?? ''
    ];
} catch (PDOException $e) {
    $_SESSION['flash'] = 'Failed to fetch settings: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

require_once 'header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="card">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Video Settings</h1>
                <p class="text-gray-400">Configure your video proxy settings</p>
            </div>

            <?php if (isset($_SESSION['flash'])): ?>
            <div id="flash-message" class="<?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-900/50 border-red-500 text-red-200' : 'bg-green-900/50 border-green-500 text-green-200'; ?> border px-4 py-3 rounded-lg mb-6 transform transition-all duration-500 ease-in-out">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <?php if ($_SESSION['flash_type'] === 'error'): ?>
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        <?php else: ?>
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm leading-5">
                            <?php echo htmlspecialchars($_SESSION['flash']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <script>
                // Animate flash message
                const flashMessage = document.getElementById('flash-message');
                if (flashMessage) {
                    // Fade in
                    flashMessage.style.opacity = '0';
                    flashMessage.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        flashMessage.style.opacity = '1';
                        flashMessage.style.transform = 'translateY(0)';
                    }, 100);

                    // Fade out after delay
                    setTimeout(() => {
                        flashMessage.style.opacity = '0';
                        flashMessage.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            flashMessage.style.display = 'none';
                        }, 500);
                    }, 5000);
                }
            </script>
            <?php unset($_SESSION['flash']); unset($_SESSION['flash_type']); endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Site Title</label>
                    <input type="text" name="site_title" required
                           class="input-field w-full"
                           value="<?php echo htmlspecialchars($settings['site_title']); ?>">
                    <p class="mt-1 text-sm text-gray-400">This will be displayed in the browser tab and header</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Favicon URL</label>
                    <input type="url" name="favicon_url"
                           class="input-field w-full"
                           value="<?php echo htmlspecialchars($settings['favicon_url']); ?>"
                           placeholder="https://example.com/favicon.ico">
                    <p class="mt-1 text-sm text-gray-400">URL to your site's favicon (recommended size: 32x32 pixels)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Advertisement URL</label>
                    <input type="url" name="ad_url"
                           class="input-field w-full"
                           value="<?php echo htmlspecialchars($settings['ad_url']); ?>"
                           placeholder="https://example.com/ads">
                    <p class="mt-2 text-sm text-gray-400">
                        Enter the URL where your video advertisements are hosted
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Allowed Domains</label>
                    <textarea name="domains"
                              class="input-field w-full h-32"
                              placeholder="example.com&#10;subdomain.example.com"><?php echo htmlspecialchars($settings['domains']); ?></textarea>
                    <p class="mt-2 text-sm text-gray-400">
                        Enter one domain per line. These domains will be allowed to embed your videos.
                    </p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/dashboard" class="btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
