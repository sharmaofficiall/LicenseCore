<?php
/**
 * Enhanced License Auth System - Core Configuration
 * Based on KeyAuth Architecture
 */

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'licenseauth');

// ===== SITE CONFIGURATION =====
define('SITE_URL', 'http://localhost/licenceauth');
define('SITE_NAME', 'LicenseAuth Pro');
define('SITE_VERSION', '2.0');

// ===== SECURITY CONFIGURATION =====
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here');

// ===== API CONFIGURATION =====
define('API_VERSION', '1.2');
define('RATE_LIMIT_REQUESTS', 200);
define('RATE_LIMIT_WINDOW', 60);

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== SECURITY HEADERS =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// ===== HELPER FUNCTIONS =====

/**
 * Check if user is logged in
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['username']) && !empty($_SESSION['username']);
    }
}

/**
 * Get current logged-in user data
 */
function get_current_user_data() {
    global $conn;
    if (is_logged_in()) {
        $username = $_SESSION['username'];
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Require user to be logged in
 */
if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header("Location: " . SITE_URL . "/login/");
            exit();
        }
    }
}

/**
 * Sanitize input data
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Generate secure random string
 */
if (!function_exists('generate_random')) {
    function generate_random($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

/**
 * Generate license key
 */
if (!function_exists('generate_license_key')) {
    function generate_license_key($format = 'random') {
        switch ($format) {
            case 'uuid':
                return sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            case 'alphanum':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $key = '';
                for ($i = 0; $i < 32; $i++) {
                    if ($i > 0 && $i % 8 == 0) $key .= '-';
                    $key .= $chars[random_int(0, strlen($chars) - 1)];
                }
                return $key;
            default: // random
                return generate_random(32);
        }
    }
}

/**
 * Generate owner ID
 */
if (!function_exists('generate_owner_id')) {
    function generate_owner_id() {
        global $conn;
        do {
            $id = strtoupper(substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 10)), 0, 10));
            $stmt = $conn->prepare("SELECT id FROM accounts WHERE ownerid = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $id;
    }
}

/**
 * Generate app secret
 */
if (!function_exists('generate_app_secret')) {
    function generate_app_secret() {
        global $conn;
        do {
            // Use full-length 64-char secrets to match the widened schema and client expectations
            $secret = generate_random(64);
            $stmt = $conn->prepare("SELECT id FROM apps WHERE secret = ?");
            $stmt->bind_param("s", $secret);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $secret;
    }
}

/**
 * Generate slug from text
 */
if (!function_exists('generate_slug')) {
    function generate_slug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

/**
 * Hash password
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

/**
 * Verify password
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Get user IP address
 */
if (!function_exists('get_ip')) {
    function get_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

/**
 * Get user agent
 */
if (!function_exists('get_user_agent')) {
    function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

/**
 * Send JSON response
 */
if (!function_exists('send_json')) {
    function send_json($success, $message, $data = null) {
        header('Content-Type: application/json');
        $response = [
            'success' => $success,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        die(json_encode($response));
    }
}

/**
 * Rate limiting function
 */
if (!function_exists('check_rate_limit')) {
    function check_rate_limit($identifier, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        if (!function_exists('apcu_fetch')) {
            // APCu not available, skip rate limiting
            return true;
        }
        global $conn;
        $key = 'rate_limit:' . $identifier;
        $current = apcu_fetch($key);

        if ($current === false) {
            apcu_store($key, 1, $window);
            return true;
        }

        if ($current >= $limit) {
            return false;
        }

        apcu_inc($key);
        return true;
    }
}

/**
 * Log action
 */
if (!function_exists('log_action')) {
    function log_action($app, $action, $user = null, $message = '', $ip = null) {
        global $conn;
        $ip = $ip ?? get_ip();
        $user = $user ?? ($_SESSION['username'] ?? 'system');
        
        $stmt = $conn->prepare("INSERT INTO logs (app, action, user, ip, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $app, $action, $user, $ip, $message);
        return $stmt->execute();
    }
}

/**
 * Encrypt data (AES-256-CBC)
 */
if (!function_exists('encrypt_data')) {
    function encrypt_data($data, $key = ENCRYPTION_KEY) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
}

/**
 * Decrypt data
 */
if (!function_exists('decrypt_data')) {
    function decrypt_data($data, $key = ENCRYPTION_KEY) {
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv);
    }
}

/**
 * Get HWID (Hardware ID)
 */
if (!function_exists('get_hwid')) {
    function get_hwid($hwid = null) {
        if ($hwid === null) {
            $hwid = $_POST['hwid'] ?? $_GET['hwid'] ?? null;
        }
        return $hwid ? sanitize($hwid) : null;
    }
}

/**
 * Ban user
 */
if (!function_exists('ban_user')) {
    function ban_user($app, $username, $reason = 'User banned') {
        global $conn;
        $stmt = $conn->prepare("UPDATE end_users SET banned = 1, ban_reason = ? WHERE app = ? AND username = ?");
        $stmt->bind_param("sss", $reason, $app, $username);
        return $stmt->execute();
    }
}

/**
 * Unban user
 */
if (!function_exists('unban_user')) {
    function unban_user($app, $username) {
        global $conn;
        $stmt = $conn->prepare("UPDATE end_users SET banned = 0, ban_reason = NULL WHERE app = ? AND username = ?");
        $stmt->bind_param("ss", $app, $username);
        return $stmt->execute();
    }
}

/**
 * Check if blacklisted
 */
if (!function_exists('is_blacklisted')) {
    function is_blacklisted($app, $type, $value) {
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM blacklist WHERE app = ? AND type = ? AND value = ?");
        $stmt->bind_param("sss", $app, $type, $value);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}

/**
 * Add to blacklist
 */
if (!function_exists('add_blacklist')) {
    function add_blacklist($app, $type, $value, $reason = '') {
        global $conn;
        $stmt = $conn->prepare("INSERT IGNORE INTO blacklist (app, type, value, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $app, $type, $value, $reason);
        return $stmt->execute();
    }
}

/**
 * Generate session ID
 */
if (!function_exists('generate_session_id')) {
    function generate_session_id($app, $ip) {
        global $conn;
        do {
            $sessionid = generate_random(32);
            $stmt = $conn->prepare("SELECT id FROM sessions WHERE id = ?");
            $stmt->bind_param("s", $sessionid);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $sessionid;
    }
}

/**
 * Get session info
 */
if (!function_exists('get_session')) {
    function get_session($sessionid, $app) {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ? AND app = ?");
        $stmt->bind_param("ss", $sessionid, $app);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}

/**
 * Time conversion
 */
if (!function_exists('time_conversion')) {
    function time_conversion($seconds) {
        if ($seconds <= 0) return "Expired";
        $units = ['year' => 31536000, 'month' => 2592000, 'day' => 86400, 'hour' => 3600, 'minute' => 60];
        foreach ($units as $unit => $value) {
            if ($seconds >= $value) {
                $time = round($seconds / $value);
                return $time . ' ' . $unit . ($time > 1 ? 's' : '');
            }
        }
        return 'Just now';
    }
}

?>
