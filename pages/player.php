<?php
// Get video data from the router
// $video variable should be available from the router in index.php

// Get site settings
$site_settings = null;
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT * FROM site_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Silently fail, use defaults
}

$site_title = $site_settings['site_title'] ?? 'Video Platform';
$favicon_url = $site_settings['favicon_url'] ?? '/favicon.ico';

// Get video title for the page title
$video_title = $video['title'] ?? 'Video Player';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($video_title); ?> - <?php echo htmlspecialchars($site_title); ?></title>
    <?php if (!empty($favicon_url)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($favicon_url); ?>">
    <?php endif; ?>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; }
        .player-container { width: 100%; height: 100vh; overflow: hidden; }
        iframe { width: 100%; height: 100%; border: 0; overflow: hidden; scrollbar-width: none; -ms-overflow-style: none; }
        iframe::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>
    <div class="player-container">
        <iframe id="gdPlayer" allowfullscreen scrolling="no"></iframe>
    </div>

    <script>
        async function loadVideo() {
            try {
                const response = await fetch('<?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://'; ?><?php echo $_SERVER['HTTP_HOST']; ?>/api.php?slug=<?php echo urlencode($slug); ?>');
                const data = await response.json();
                
                if (data.status === 'success' && data.embed_url) {
                    document.getElementById('gdPlayer').src = data.embed_url;
                } else {
                    console.error('Failed to load video:', data.message);
                }
            } catch (error) {
                console.error('Error loading video:', error);
            }
        }

        loadVideo();
    </script>
</body>
</html>
