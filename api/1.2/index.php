<?php
/**
 * Enhanced License Authentication API v1.2
 * KeyAuth Compatible - Full Integration
 * Supports: Init, Register, Login, License, Check, Variables, Webhooks, Chat, Download
 */

// Set error handler before anything else
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/../../logs/api_errors.log');

// Set timezone to IST
date_default_timezone_set('Asia/Calcutta');

// Global exception handler
set_exception_handler(function($exception) {
    http_response_code(200); // Keep 200 for API compatibility
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $exception->getMessage()
    ]);
    error_log('API Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    exit;
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, signature');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/enhanced.php';
    
// Global response function with signature support
// When $flat is true, $data fields are merged at top-level (KeyAuth expected shape)
// $data_key controls the property name when not flat (default 'data', but can be 'info')
function send_response($success, $message, $data = null, $enckey = null, $flat = false, $data_key = 'data') {
    try {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            if ($flat && is_array($data)) {
                // Merge data fields into top-level
                foreach ($data as $k => $v) {
                    $response[$k] = $v;
                }
            } else {
                $response[$data_key] = $data;
            }
        }
        
        // Generate signature if enckey provided
        if ($enckey) {
            $response_json = json_encode($response);
            $signature = hash_hmac('sha256', $response_json, $enckey);
            header('signature: ' . $signature);
            error_log('Response with signature: ' . $response_json);
            error_log('Signature: ' . $signature);
            error_log('Enckey used for signature: ' . $enckey . ' (length: ' . strlen($enckey) . ')');
        } else {
            error_log('Response without signature: ' . json_encode($response));
            error_log('No enckey provided for signature');
        }
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log('send_response error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Response generation failed']);
    }
    exit;
}

// Rate limiting check
$identifier = get_ip();
if (!check_rate_limit($identifier)) {
    send_response(false, "Too many requests. Please try again later.");
}

// Handle JSON POST data (KeyAuth C# client sends JSON)
$input_data = $_POST;
if (empty($_POST) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    if ($raw_input) {
        $json_data = json_decode($raw_input, true);
        if (is_array($json_data)) {
            $input_data = $json_data;
        }
    }
}

// Log incoming request for debugging
error_log('=== INCOMING REQUEST ===');
error_log('Input Data: ' . json_encode($input_data));
error_log('GET: ' . json_encode($_GET));

// Get request parameters (KeyAuth compatible)
$ownerid = sanitize($input_data['ownerid'] ?? $_GET['ownerid'] ?? '');
$name = sanitize($input_data['name'] ?? $_GET['name'] ?? '');
$ver_param = sanitize($input_data['ver'] ?? $_GET['ver'] ?? '');
$type_param = sanitize($input_data['type'] ?? $_GET['type'] ?? '');
$sessionid_param = sanitize($input_data['sessionid'] ?? $_GET['sessionid'] ?? '');

// Extract other common parameters
$hwid_param = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? '');

error_log("Received - ownerid: '$ownerid', name: '$name', type: '$type_param'");

// Validate app credentials
if (empty($ownerid) || empty($name)) {
    error_log('VALIDATION FAILED - Missing ownerid or name');
    send_response(false, "Invalid application credentials");
}

// Look up app secret from database (KeyAuth doesn't send secret in requests)
$app = get_app_details($ownerid, $name);
if (!$app) {
    error_log('VALIDATION FAILED - App not found in database');
    send_response(false, "Invalid application credentials");
}

$secret = $app['secret'];
$aid_param = $secret; // app identifier
$app_secret = $secret; // Use the secret we already fetched
error_log("App found - secret: " . substr($secret, 0, 8) . "...");

// Route to appropriate endpoint

// Resolve enckey for signing responses (uses session, if provided)
function resolve_enckey($sessionid, $app_secret) {
    if (empty($sessionid)) {
        error_log('resolve_enckey: No sessionid provided');
        return null;
    }
    $session = get_session($sessionid, $app_secret);
    if ($session) {
        error_log('resolve_enckey: Session found, enckey: ' . $session['enckey'] . ' (length: ' . strlen($session['enckey']) . ')');
        return $session['enckey'];
    } else {
        error_log('resolve_enckey: Session not found for sessionid: ' . $sessionid);
        return null;
    }
}

// Enforce session presence for actions that must be signed
function require_session_with_enckey($sessionid, $app_secret) {
    $session = get_session($sessionid, $app_secret);
    if (!$session) {
        send_response(false, "Signature check failed");
    }
    return $session;
}

$response_enckey = resolve_enckey($sessionid_param, $app_secret);

// Helper function to get app details by ownerid and name
function get_app_details($ownerid, $name) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM apps WHERE ownerid = ? AND name = ?");
    $stmt->bind_param("ss", $ownerid, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Check if app is paused
if ($app['paused']) {
    send_response(false, "This application is currently paused");
}

// ===== CORE FUNCTIONS =====

function initialize_session($app_secret) {
    global $conn;
    $sessionid = generate_session_id($app_secret, get_ip());
    $expiry = time() + 3600;
    $ip = get_ip();
    $useragent = get_user_agent();
        // Generate enckey in format that C# client expects
        // C# does: enckey.Substring(17, 64) for init
        // So we create: [17 chars prefix] + [64 char key] = 81 chars total
        $prefix = bin2hex(random_bytes(8)) . '0'; // 17 characters
        $key = bin2hex(random_bytes(32)); // 64 characters
        $enckey = $prefix . $key; // 81 characters total
    
    $stmt = $conn->prepare("INSERT INTO `sessions` (id, app, expiry, ip, useragent, enckey) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $sessionid, $app_secret, $expiry, $ip, $useragent, $enckey);
    $stmt->execute();
    
    return ['sessionid' => $sessionid, 'enckey' => $enckey];
}

function register_user($app_secret, $username, $key, $password, $hwid = null, $email = null) {
    global $conn;
    
    // Validate input
    if (strlen($username) < 3 || strlen($username) > 70) {
        return ['success' => false, 'message' => 'Username must be 3-70 characters'];
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM end_users WHERE app = ? AND username = ?");
    $stmt->bind_param("ss", $app_secret, $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Username already taken'];
    }
    
    // Validate license key
    $stmt = $conn->prepare("SELECT * FROM `keys` WHERE app = ? AND license_key = ? AND status = 'active'");
    $stmt->bind_param("ss", $app_secret, $key);
    $stmt->execute();
    $key_result = $stmt->get_result();
    
    if ($key_result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid or expired license key'];
    }
    
    $key_data = $key_result->fetch_assoc();
    
    // Check key expiry
    if ($key_data['expiry'] > 0 && $key_data['expiry'] < time()) {
        return ['success' => false, 'message' => 'License key has expired'];
    }
    
    // Check if blacklisted
    if (is_blacklisted($app_secret, 'username', $username)) {
        return ['success' => false, 'message' => 'Username is blacklisted'];
    }
    
    if ($hwid && is_blacklisted($app_secret, 'hwid', $hwid)) {
        return ['success' => false, 'message' => 'Hardware ID is blacklisted'];
    }
    
    // Hash password
    $password_hash = hash_password($password);
    $ip = get_ip();
    
    // Create user
    $stmt = $conn->prepare("INSERT INTO end_users (app, username, email, password, hwid, ip) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $app_secret, $username, $email, $password_hash, $hwid, $ip);
    
    if ($stmt->execute()) {
        // Update key as used
        $stmt = $conn->prepare("UPDATE keys SET status = 'inactive', used_by = ?, used_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $username, $key_data['id']);
        $stmt->execute();
        
        // Log action
        log_action($app_secret, 'register', $username, 'User registered', $ip);
        
        return ['success' => true, 'message' => 'User registered successfully'];
    }
    
    return ['success' => false, 'message' => 'Registration failed'];
}

function login_user($app_secret, $username, $password, $hwid = null) {
    global $conn;
    
    // Sanitize input
    $username = sanitize($username);
    
    // Get user
    $stmt = $conn->prepare("SELECT * FROM end_users WHERE app = ? AND username = ?");
    $stmt->bind_param("ss", $app_secret, $username);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        return ['success' => false, 'message' => 'Username not found'];
    }
    
    $user = $user_result->fetch_assoc();
    
    // Check if banned
    if ($user['banned']) {
        $reason = $user['ban_reason'] ?? 'Banned';
        return ['success' => false, 'message' => 'User banned: ' . $reason];
    }
    
    // Verify password
    if (!verify_password($password, $user['password'])) {
        log_action($app_secret, 'login_failed', $username, 'Invalid password', get_ip());
        return ['success' => false, 'message' => 'Invalid password'];
    }
    
    // Check HWID
    if ($user['hwid'] && $hwid && $user['hwid'] !== $hwid) {
        log_action($app_secret, 'hwid_mismatch', $username, "HWID mismatch: {$user['hwid']} vs $hwid", get_ip());
        return ['success' => false, 'message' => 'HWID mismatch'];
    }
    
    // Update last login
    $stmt = $conn->prepare("UPDATE end_users SET last_login = NOW(), ip = ? WHERE id = ?");
    $ip = get_ip();
    $stmt->bind_param("si", $ip, $user['id']);
    $stmt->execute();
    
    // Log action
    log_action($app_secret, 'login', $username, 'User logged in', $ip);
    
    // Get user subscriptions
    $stmt = $conn->prepare("SELECT * FROM subs WHERE app = ? AND user = ? AND expiry > ?");
    $expiry_time = time();
    $stmt->bind_param("ssi", $app_secret, $username, $expiry_time);
    $stmt->execute();
    $subs_result = $stmt->get_result();
    
    $subscriptions = [];
    while ($sub = $subs_result->fetch_assoc()) {
        $subscriptions[] = [
            'subscription' => $sub['sub_name'],
            'expiry' => $sub['expiry'],
            'timeleft' => time_conversion($sub['expiry'] - time())
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Logged in successfully',
        'info' => [
            'username' => $user['username'],
            'email' => $user['email'],
            'hwid' => $user['hwid'],
            'ip' => $user['ip'],
            'created_at' => strtotime($user['created_at']),
            'last_login' => $user['last_login'] ? strtotime($user['last_login']) : null,
            'subscriptions' => $subscriptions
        ]
    ];
}

// ===== ACTION HANDLERS =====

switch ($type_param) {
    case 'init':
        // Get client-supplied enckey (optional, max 35 chars like KeyAuth)
        $client_enckey = sanitize($input_data['enckey'] ?? $_GET['enckey'] ?? '');
        if (!empty($client_enckey) && strlen($client_enckey) > 35) {
            send_response(false, "The parameter \"enckey\" is too long. Must be 35 characters or less.", null, null);
        }
        
        // App details already fetched earlier, no need to fetch again
        
        // Version check is optional - only validate if both client and server versions exist
        if (!empty($ver_param) && !empty($app['version']) && $ver_param != $app['version']) {
            send_response(false, "Invalid version", null, $app_secret);
        }
        
        // Combine client enckey with secret (KeyAuth style)
        $enckey = !empty($client_enckey) ? $client_enckey . "-" . $app_secret : NULL;
        
        // Create session in database
        $sessionid = bin2hex(random_bytes(16));
        $expiry = time() + 3600;
        $ip = get_ip();
        $useragent = get_user_agent();
        
        $stmt = $conn->prepare("INSERT INTO `sessions` (id, app, expiry, ip, useragent, enckey, validated) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssisss", $sessionid, $app_secret, $expiry, $ip, $useragent, $enckey);
        $stmt->execute();
        
        // Calculate stats
        $stmt = $conn->prepare("SELECT 
            (SELECT COUNT(*) FROM end_users WHERE app = ?) as numUsers,
            (SELECT COUNT(*) FROM `sessions` WHERE app = ? AND validated = 1 AND expiry > ?) as numOnlineUsers,
            (SELECT COUNT(*) FROM `keys` WHERE app = ? AND status = 'active') as numKeys
        ");
        $time = time();
        $stmt->bind_param("sssi", $app_secret, $app_secret, $time, $app_secret);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        // CRITICAL: Init response is signed with just $app_secret (not combined enckey)
        // This matches KeyAuth behavior - client verifies init with app secret only
        // Also flatten fields to top-level to match C# library expectations
        send_response(true, "Initialized", [
            "sessionid" => $sessionid,
            "appinfo" => [
                "numUsers" => (string)($stats['numUsers'] ?? '0'),
                "numOnlineUsers" => (string)($stats['numOnlineUsers'] ?? '0'),
                "numKeys" => (string)($stats['numKeys'] ?? '0'),
                "version" => $app['version'] ?? '1.0',
                "customerPanelLink" => $app['custom_domain'] ?? ''
            ],
            "nonce" => bin2hex(random_bytes(16))
        ], $app_secret, true, 'data');  // Sign with secret only, not combined enckey; flatten
        break;
    
    case 'register':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $username = sanitize($input_data['username'] ?? $_GET['username'] ?? '');
        $password = $input_data['pass'] ?? $_GET['pass'] ?? '';
        $key = sanitize($input_data['key'] ?? $_GET['key'] ?? '');
        $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);
        $email = sanitize($input_data['email'] ?? $_GET['email'] ?? null);
        
        $result = register_user($app_secret, $username, $key, $password, $hwid, $email);
        $response_data = $result['success'] ? ['nonce' => bin2hex(random_bytes(16))] : null;
        send_response($result['success'], $result['message'], $response_data, $app_secret, false, 'info');
        break;
    
    case 'login':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $username = sanitize($input_data['username'] ?? $_GET['username'] ?? '');
        $password = $input_data['pass'] ?? $_GET['pass'] ?? '';
        $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);
        
        $result = login_user($app_secret, $username, $password, $hwid);
        if ($result['success'] && isset($result['info'])) {
            $result['info']['nonce'] = bin2hex(random_bytes(16));
        }
        send_response($result['success'], $result['message'], $result['success'] ? $result['info'] : null, $app_secret, false, 'info');
        break;
    
    case 'license':
        try {
            // Validate license key directly (KeyAuth style)
            // Note: License validation doesn't require a session in KeyAuth
            $key = sanitize($input_data['key'] ?? $_GET['key'] ?? '');
            $hwid = sanitize($input_data['hwid'] ?? $_GET['hwid'] ?? null);

        if (empty($key)) {
            send_response(false, "License key required");
        }
        
        // Get license key from database
        $stmt = $conn->prepare("SELECT * FROM `keys` WHERE app = ? AND license_key = ?");
        $stmt->bind_param("ss", $app_secret, $key);
        $stmt->execute();
        $key_result = $stmt->get_result();
        
        if ($key_result->num_rows === 0) {
            // Extra diagnostics: log available keys for this app
            $diagStmt = $conn->prepare("SELECT license_key, status, expiry FROM `keys` WHERE app = ? ORDER BY id DESC LIMIT 5");
            $diagStmt->bind_param("s", $app_secret);
            $diagStmt->execute();
            $diagRes = $diagStmt->get_result();
            $available = [];
            while ($row = $diagRes->fetch_assoc()) {
                $available[] = $row;
            }
            error_log('License lookup failed. app=' . substr($app_secret,0,8) . ' key=' . $key . ' available_keys=' . json_encode($available));

            log_action($app_secret, 'license_invalid', $key, "Invalid license key attempt", get_ip());
            send_response(false, "Invalid license key");
        }
        
        $license = $key_result->fetch_assoc();
        
        // Check if key is expired
        if ($license['expiry'] > 0 && $license['expiry'] < time()) {
            log_action($app_secret, 'license_expired', $key, "Expired license key attempt", get_ip());
            send_response(false, "License key has expired");
        }

        // Check if key is already used
        if ($license['status'] !== 'active') {
            log_action($app_secret, 'license_used', $key, "Already used license key attempt", get_ip());
            send_response(false, "License key has already been used or is inactive");
        }

        // Check blacklist
        if (is_blacklisted($app_secret, 'hwid', $hwid) || is_blacklisted($app_secret, 'key', $key)) {
            log_action($app_secret, 'license_blacklist', $key, "Blacklisted HWID/key attempt", get_ip());
            send_response(false, "This license or hardware is blacklisted");
        }
        
        // Return license info - align with C# user_data_structure field names
        $user_data = [
            'username'   => 'license_' . bin2hex(random_bytes(4)),
            'ip'         => get_ip(),
            'hwid'       => $hwid ?? '',
            'createdate' => (string)time(),
            'lastlogin'  => (string)time(),
            'subscriptions' => [
                [
                    'subscription' => 'Premium',
                    'expiry' => (string)$license['expiry'],
                    'timeleft' => ($license['expiry'] > 0) ? time_conversion($license['expiry'] - time()) : 'Lifetime'
                ]
            ]
        ];

        error_log('License response: ' . json_encode($user_data));
        log_action($app_secret, 'license_valid', $key, "Valid license used", get_ip());
        send_response(true, "License is valid", $user_data, $app_secret, false, 'info');  // Place under 'info' for C# client
        } catch (Exception $e) {
            error_log('License endpoint error: ' . $e->getMessage());
            send_response(false, "License validation failed: " . $e->getMessage());
        }
        break;
    
    case 'check':
        // Check if a user/session is still valid
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (time() > $session['expiry']) {
            send_response(false, "Session expired", null, $session['enckey']);
        }
        
        send_response(true, "Session valid", [
            'sessionid' => $session['id'],
            'nonce' => bin2hex(random_bytes(16))
        ], $app_secret);
        break;
    
    case 'validate':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (time() > $session['expiry']) {
            send_response(false, "Session expired", null, $session['enckey']);
        }
        
        send_response(true, "Session valid", [
            'sessionid' => $session['id'],
            'nonce' => bin2hex(random_bytes(16))
        ], $app_secret);
        break;
    
    case 'fetchstats':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $stmt = $conn->prepare("SELECT
            (SELECT COUNT(*) FROM end_users WHERE app = ?) as numUsers,
            (SELECT COUNT(*) FROM `sessions` WHERE app = ? AND validated = 1 AND expiry > ?) as numOnlineUsers,
            (SELECT COUNT(*) FROM `keys` WHERE app = ? AND status = 'active') as numKeys
        ");
        $time = time();
        $stmt->bind_param("sssi", $app_secret, $app_secret, $time, $app_secret);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        send_response(true, "Stats fetched", [
            'appinfo' => [
                'numUsers' => (string)$result['numUsers'],
                'numOnlineUsers' => (string)$result['numOnlineUsers'],
                'numKeys' => (string)$result['numKeys'],
                'version' => $app['version'] ?? '1.0'
            ],
            'nonce' => bin2hex(random_bytes(16))
        ], $app_secret);
        break;
    
    case 'getvar':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $varid = sanitize($_POST['varid'] ?? $_GET['varid'] ?? '');
        
        if (empty($varid)) {
            send_response(false, "Variable ID required", null, $session['enckey']);
        }
        
        $stmt = $conn->prepare("SELECT msg, authed FROM vars WHERE app = ? AND varid = ?");
        $stmt->bind_param("ss", $app_secret, $varid);
        $stmt->execute();
        $var = $stmt->get_result()->fetch_assoc();
        
        if (!$var) {
            send_response(false, "Variable not found", null, $session['enckey']);
        }
        
        send_response(true, "Variable fetched", [
            'response' => $var['msg'],
            'nonce' => bin2hex(random_bytes(16))
        ], $app_secret);
        break;
    
    case 'ban':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        
        if (!$session['validated']) {
            send_response(false, "Session not authenticated", null, $session['enckey']);
        }
        
        $username = $session['credential'];
        $reason = sanitize($_POST['reason'] ?? $_GET['reason'] ?? 'User banned from client');
        
        if (ban_user($app_secret, $username, $reason)) {
            send_response(true, "User banned successfully", [
                'nonce' => bin2hex(random_bytes(16))
            ], $app_secret);
        }
        
        send_response(false, "Failed to ban user", null, $session['enckey']);
        break;
    
    case 'checkblacklist':
        $session = require_session_with_enckey($sessionid_param, $app_secret);
        $type = sanitize($_POST['type'] ?? $_GET['type'] ?? '');
        $value = sanitize($_POST['value'] ?? $_GET['value'] ?? '');
        
        if (is_blacklisted($app_secret, $type, $value)) {
            send_response(true, "Value is blacklisted", [
                'nonce' => bin2hex(random_bytes(16))
            ], $app_secret);
        }
        
        send_response(false, "Value is not blacklisted", [
            'nonce' => bin2hex(random_bytes(16))
        ], $app_secret);
        break;
    
    default:
        send_response(false, "Unknown action: $type_param", null, $response_enckey);
}

?>
