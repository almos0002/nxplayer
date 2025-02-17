<?php
require_once 'config.php';

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// Set CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Validation functions
function isValidFileId($id) {
    return preg_match('/^[a-zA-Z0-9_-]{25,}$/', $id);
}

function isValidAdDomain($url) {
    $allowedDomains = ['monetag.com', 'hilltopads.net', 'richads.com'];
    $domain = parse_url($url, PHP_URL_HOST);
    return $domain && in_array($domain, $allowedDomains);
}

// Get video by slug
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Slug parameter is required']));
}

try {
    $stmt = $db->prepare("
        SELECT v.*, vs.ad_url, vs.domains 
        FROM videos v 
        LEFT JOIN video_settings vs ON 1=1 
        WHERE v.slug = ? AND v.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$slug, $_SESSION['user_id']]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        http_response_code(404);
        die(json_encode(['status' => 'error', 'message' => 'Video not found']));
    }

    // Get the latest video settings
    $stmt = $db->prepare("SELECT * FROM video_settings WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [
        'id' => $video['id'],
        'file_id' => $video['file_id'],
        'title' => $video['title'],
        'subtitle' => $video['subtitle'],
        'ad_url' => $settings['ad_url'] ?? null,
        'domains' => $settings['domains'] ?? null
    ];

    $ch = curl_init('https://gdplayer.vip/api/video');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($response));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $apiResponse = curl_exec($ch);
    
    if (curl_errno($ch)) {
        http_response_code(500);
        die(json_encode(['status' => 'error', 'message' => 'API request failed: ' . curl_error($ch)]));
    }
    
    curl_close($ch);
    
    $result = json_decode($apiResponse, true);
    if ($result && isset($result['data']['embed_url'])) {
        echo json_encode(['status' => 'success', 'embed_url' => $result['data']['embed_url']]);
    } else {
        http_response_code(500);
        die(json_encode(['status' => 'error', 'message' => 'Invalid API response', 'debug' => $apiResponse]));
    }

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]));
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]));
}
