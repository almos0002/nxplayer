<?php
require_once '../config.php';
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
            $stmt = $db->prepare("UPDATE site_settings SET value = ? WHERE setting = 'site_title'");
            $stmt->execute([$_POST['site_title']]);
        }
        
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === 0) {
            $favicon = $_FILES['favicon'];
            $allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg'];
            
            if (in_array($favicon['type'], $allowedTypes)) {
                $uploadPath = '../favicon.' . pathinfo($favicon['name'], PATHINFO_EXTENSION);
                move_uploaded_file($favicon['tmp_name'], $uploadPath);
                
                $stmt = $db->prepare("UPDATE site_settings SET value = ? WHERE setting = 'favicon'");
                $stmt->execute([$uploadPath]);
            }
        }
    }
    
    // User settings (can be expanded later)
    header("Location: settings.php?success=1");
    exit;
}

// Create site_settings table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting VARCHAR(50) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default settings if they don't exist
$defaultSettings = [
    'site_title' => 'Proxy Player',
    'favicon' => '/favicon.ico'
];

foreach ($defaultSettings as $setting => $value) {
    $stmt = $db->prepare("INSERT IGNORE INTO site_settings (setting, value) VALUES (?, ?)");
    $stmt->execute([$setting, $value]);
}

// Get current settings
$stmt = $db->prepare("SELECT * FROM site_settings");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include '../header.php'; ?>

<div class="container mt-5">
    <h2>Settings</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Settings updated successfully!</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php if ($userRole === 'admin'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Site Settings (Admin Only)</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_title" class="form-label">Site Title</label>
                        <input type="text" class="form-control" id="site_title" name="site_title" 
                               value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="favicon" class="form-label">Favicon</label>
                        <input type="file" class="form-control" id="favicon" name="favicon" accept=".ico,.png,.jpg,.jpeg">
                        <?php if (isset($settings['favicon'])): ?>
                            <div class="mt-2">
                                <small>Current favicon: <img src="<?php echo htmlspecialchars($settings['favicon']); ?>" 
                                                          alt="Current favicon" style="width: 16px; height: 16px;"></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4>User Settings</h4>
            </div>
            <div class="card-body">
                <!-- Add user-specific settings here -->
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
    </form>
</div>

<?php include '../footer.php'; ?>
