<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'licenseauth');

// Site configuration
define('SITE_URL', 'http://localhost/licenceauth');
define('SITE_NAME', 'LicenseCore');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select database
$conn->select_db(DB_NAME);

// Session settings
session_start();
define('SESSION_TIMEOUT', 3600); // 1 hour

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Helper function to check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// Helper function to get current user
function get_current_user_data() {
    global $conn;
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        return $result->fetch_assoc();
    }
    return null;
}

// Redirect if not logged in
if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            header("Location: " . SITE_URL . "/login.php");
            exit();
        }
    }
}

// Sanitize input
if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

// Generate license key
if (!function_exists('generate_license_key')) {
    function generate_license_key() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        for ($i = 0; $i < 32; $i++) {
            if ($i > 0 && $i % 8 == 0) {
                $key .= '-';
            }
            $key .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $key;
    }
}

// Generate unique slug
if (!function_exists('generate_slug')) {
    function generate_slug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}
?>