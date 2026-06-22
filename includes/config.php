<?php
// PHP session management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'unsent');

try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper: Sanitize input values
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper: Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: Check if logged in user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Helper: Check if logged in user is premium
function isPremium() {
    // Refresh premium status dynamically
    if (isLoggedIn()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT premium_status, premium_until FROM users WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['premium_status'] == 1 && $user['premium_until'] !== null) {
                $until = strtotime($user['premium_until']);
                if ($until < time()) {
                    // Premium expired, update DB status
                    $update = $pdo->prepare("UPDATE users SET premium_status = 0 WHERE id_user = ?");
                    $update->execute([$_SESSION['user_id']]);
                    $_SESSION['user_premium'] = 0;
                    return false;
                }
                $_SESSION['user_premium'] = 1;
                return true;
            }
        }
    }
    return false;
}

// Helper: Enforce active login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Helper: Enforce administrator access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard_user.php");
        exit;
    }
}

// Helper: Get premium status details
function getPremiumExpiry() {
    if (isLoggedIn()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT premium_until FROM users WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        return $res ? $res['premium_until'] : null;
    }
    return null;
}

// Helper: Simple money formatting (IDR)
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Helper: Fetch Spotify track metadata from oEmbed
function getSpotifyMetadata($spotify_url) {
    if (empty($spotify_url)) {
        return null;
    }
    
    // Check if it's a valid Spotify track URL
    if (!preg_match('/^https?:\/\/(?:open|play)\.spotify\.com\/(?:[a-zA-Z\-]{2,5}\/)?track\/([a-zA-Z0-9]+)/i', $spotify_url)) {
        return null;
    }

    $oembed_url = "https://open.spotify.com/oembed?url=" . urlencode($spotify_url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $oembed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36");
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && !empty($response)) {
        $data = json_decode($response, true);
        if ($data) {
            return [
                'title' => $data['title'] ?? '',
                'artist' => $data['author_name'] ?? '',
                'cover_image' => $data['thumbnail_url'] ?? ''
            ];
        }
    }
    return null;
}
?>
