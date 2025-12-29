<?php
require_once '../includes/enhanced.php';
require_login();

$user = get_current_user_data();
$ownerid = $user['ownerid'];

// Get current page
$page = sanitize($_GET['page'] ?? 'home');
$selected_app = sanitize($_GET['app'] ?? '');

// Handle app creation
$create_error = '';
$create_success = '';
$app_error = '';
$app_success = '';

$license_error = '';
$license_success = '';
$generated_keys = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Create application
    if ($action === 'create_app') {
        $app_name = sanitize($_POST['app_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $version = sanitize($_POST['version'] ?? '1.0');
        
        if (empty($app_name)) {
            $create_error = 'App name is required';
        } else {
            $secret = generate_app_secret();
            
            $stmt = $conn->prepare("INSERT INTO apps (secret, name, ownerid, version, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $secret, $app_name, $ownerid, $version, $description);
            
            if ($stmt->execute()) {
                $create_success = 'Application created successfully!';
                log_action($secret, 'app_created', $user['username'], 'New application created');
            } else {
                $create_error = 'Error creating application';
            }
        }
    }

    // Reset application secret
    if ($action === 'reset_secret') {
        $app_secret = sanitize($_POST['app'] ?? '');

        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_row = $stmt->get_result()->fetch_assoc();

        if (!$app_row) {
            $app_error = 'Invalid application selected.';
        } else {
            $new_secret = generate_app_secret();

            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                // Update primary app secret
                $stmtUpdateApp = $conn->prepare("UPDATE apps SET secret = ?, modified_at = NOW() WHERE secret = ? AND ownerid = ?");
                if (!$stmtUpdateApp) {
                    throw new Exception('Prepare apps failed: ' . $conn->error);
                }
                $stmtUpdateApp->bind_param("sss", $new_secret, $app_secret, $ownerid);
                if (!$stmtUpdateApp->execute()) {
                    throw new Exception('Update apps failed: ' . $stmtUpdateApp->error);
                }
                if ($stmtUpdateApp->affected_rows === 0) {
                    throw new Exception('No app updated');
                }

                // Propagate new secret across dependent tables
                $tables = [
                    'subscriptions', 'end_users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations', 'subs'
                ];

                foreach ($tables as $table) {
                    $stmtUpdate = $conn->prepare("UPDATE {$table} SET app = ? WHERE app = ?");
                    if (!$stmtUpdate) {
                        throw new Exception("Prepare {$table} failed: " . $conn->error);
                    }
                    $stmtUpdate->bind_param("ss", $new_secret, $app_secret);
                    if (!$stmtUpdate->execute()) {
                        throw new Exception("Update {$table} failed: " . $stmtUpdate->error);
                    }
                }

                // Also update logapp mirror field when it matches the old secret
                $stmtLogapp = $conn->prepare("UPDATE logs SET logapp = ? WHERE logapp = ?");
                if (!$stmtLogapp) {
                    throw new Exception('Prepare logapp failed: ' . $conn->error);
                }
                $stmtLogapp->bind_param("ss", $new_secret, $app_secret);
                if (!$stmtLogapp->execute()) {
                    throw new Exception('Update logapp failed: ' . $stmtLogapp->error);
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $conn->commit();

                $app_success = 'App secret reset successfully. Update your clients with the new secret key.';
                $selected_app = $new_secret;
                log_action($new_secret, 'secret_reset', $user['username'], 'Application secret rotated');
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $app_error = 'Failed to reset the secret. ' . $e->getMessage();
                log_action($app_secret, 'secret_reset_failed', $user['username'], $app_error);
            }
        }
    }

    // Generate license keys
    if ($action === 'generate_key') {
        $app_secret = sanitize($_POST['app'] ?? '');
        $duration = max(intval($_POST['duration'] ?? 30), 1);
        $duration_unit = sanitize($_POST['duration_unit'] ?? 'days');
        $amount = min(max(intval($_POST['amount'] ?? 1), 1), 100);
        $level = min(max(intval($_POST['level'] ?? 1), 1), 5);
        $note = sanitize($_POST['note'] ?? '');
        $key_format = sanitize($_POST['format'] ?? 'default');

        // Normalize format to match generator
        if ($key_format === 'default') {
            $key_format = 'uuid';
        }

        // Verify app ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_check = $stmt->get_result();

        if ($app_check->num_rows > 0) {
            // Determine expiry interval
            $unit = $duration_unit === 'hours' ? 'hours' : 'days';
            $expiry_ts = time() + ($duration * ($unit === 'hours' ? 3600 : 86400));

            // Generate multiple keys
            for ($i = 0; $i < $amount; $i++) {
                $license_key = generate_license_key($key_format);
                $status = 'active';

                $stmt = $conn->prepare("INSERT INTO `keys` (app, license_key, expiry, status, format, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisss", $app_secret, $license_key, $expiry_ts, $status, $key_format, $user['username']);

                if ($stmt->execute()) {
                    $generated_keys[] = $license_key;
                } else {
                    $license_error = 'Error generating some keys';
                    break;
                }
            }

            if (empty($license_error)) {
                $license_success = count($generated_keys) . ' license key(s) generated successfully!';
                log_action($app_secret, 'keys_generated', $user['username'], count($generated_keys) . ' keys generated');
            }
        } else {
            $license_error = 'Invalid application';
        }
    }

    // Delete license key
    if ($action === 'delete_key') {
        $key_id = intval($_POST['key_id'] ?? 0);
        $app_secret = sanitize($_POST['app'] ?? '');

        // Verify ownership
        $stmt = $conn->prepare("SELECT k.* FROM `keys` k JOIN apps a ON k.app = a.secret WHERE k.id = ? AND a.ownerid = ?");
        $stmt->bind_param("is", $key_id, $ownerid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $key_id);
            if ($stmt->execute()) {
                $license_success = 'License key deleted successfully';
                log_action($app_secret, 'key_deleted', $user['username'], 'License key deleted');
            }
        }
    }

    // Delete application
    if ($action === 'delete_app') {
        $app_secret = sanitize($_POST['app'] ?? '');
        error_log("Delete app attempt: app_secret=$app_secret, ownerid=$ownerid");

        // Verify ownership
        $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
        $stmt->bind_param("ss", $app_secret, $ownerid);
        $stmt->execute();
        $app_check = $stmt->get_result();
        error_log("App check result: " . $app_check->num_rows . " rows");

        if ($app_check->num_rows > 0) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

                // Delete from all dependent tables
                $tables = [
                    'subs', 'end_users', '`keys`', 'sessions', 'uservars', 'vars',
                    'files', 'webhooks', 'logs', 'blacklist', 'chats', 'chat_messages',
                    'tokens', 'integrations'
                ];

                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM {$table} WHERE app = ?");
                    $stmt->bind_param("s", $app_secret);
                    $stmt->execute();
                }

                // Finally delete the app
                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ? AND ownerid = ?");
                $stmt->bind_param("ss", $app_secret, $ownerid);
                if ($stmt->execute()) {
                    $conn->commit();
                    $app_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted', $user['username'], 'Application deleted');
                    // Redirect to applications page
                    header("Location: ?page=applications");
                    exit();
                } else {
                    throw new Exception('Failed to delete application: ' . $stmt->error);
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $app_error = 'Failed to delete application: ' . $e->getMessage();
            }
        } else {
            $app_error = 'Application not found or access denied';
        }
    }
}

// Get user's applications
$stmt = $conn->prepare("SELECT * FROM apps WHERE ownerid = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $ownerid);
$stmt->execute();
$apps_result = $stmt->get_result();
$applications = [];

while ($app = $apps_result->fetch_assoc()) {
    $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM `keys` WHERE app = ? AND status = 'active'");
    $stmt2->bind_param("s", $app['secret']);
    $stmt2->execute();
    $app['active_keys'] = $stmt2->get_result()->fetch_assoc()['count'];

    $stmt3 = $conn->prepare("SELECT COUNT(*) as count FROM `end_users` WHERE app = ?");
    $stmt3->bind_param("s", $app['secret']);
    $stmt3->execute();
    $app['total_users'] = $stmt3->get_result()->fetch_assoc()['count'];

    $applications[] = $app;
}

// Get stats
$stmt = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM apps WHERE ownerid = ?) as total_apps,
    (SELECT COUNT(*) FROM `keys` k JOIN apps a ON k.app = a.secret WHERE a.ownerid = ? AND k.status = 'active') as total_keys,
    (SELECT COUNT(*) FROM `end_users` u JOIN apps a ON u.app = a.secret WHERE a.ownerid = ?) as total_users
");
$stmt->bind_param("sss", $ownerid, $ownerid, $ownerid);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get selected app details if selected
$selected_app_data = null;
if (!empty($selected_app)) {
    $stmt = $conn->prepare("SELECT * FROM apps WHERE secret = ? AND ownerid = ?");
    $stmt->bind_param("ss", $selected_app, $ownerid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $selected_app_data = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            scrollbar-width: thin;
            scrollbar-color: #3a3a5e #1a1a2e;
        }
        *::-webkit-scrollbar {
            width: 6px;
        }
        *::-webkit-scrollbar-track {
            background: #1a1a2e;
        }
        *::-webkit-scrollbar-thumb {
            background: #3a3a5e;
            border-radius: 10px;
        }
        *::-webkit-scrollbar-thumb:hover {
            background: #4a4a6e;
        }
        body {
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f1a 100%);
            background-attachment: fixed;
        }
        .sidebar-active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-left: 3px solid #3b82f6;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
        }
        .hover-glow:hover {
            box-shadow: 0 4px 25px rgba(59, 130, 246, 0.25);
            transform: translateY(-2px);
        }
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .smooth-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-item {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .sidebar-item:hover {
            transform: translateX(4px);
            background: rgba(59, 130, 246, 0.08);
        }
        .app-badge {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        .submenu-item {
            transition: all 0.2s ease;
            border-left: 2px solid transparent;
        }
        .submenu-item:hover {
            border-left-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
            padding-left: 1rem;
        }
        .glass-effect {
            background: rgba(26, 26, 46, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(59, 130, 246, 0.1);
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.5); }
            50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8); }
        }
        .notification-dot {
            animation: pulse-glow 2s infinite;
        }
    </style>
</head>
<body class="bg-[#09090d] text-white">

<!-- Main Container -->
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div class="w-64 bg-gradient-to-b from-[#1a1a2e] to-[#15152a] border-r border-[#2a2a4e] flex flex-col overflow-y-auto shadow-2xl">
        <!-- Logo & Branding -->
        <div class="p-6 border-b border-[#2a2a4e] bg-gradient-to-br from-[#1f1f3a] to-[#1a1a2e]">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-purple-600 flex items-center justify-center shadow-lg app-badge">
                    <i class="fas fa-key text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-xl gradient-text">LicenseCore</h1>
                    <p class="text-xs text-gray-400 font-medium">Professional v2.0</p>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-[#2a2a4e]">
            <div class="glass-effect rounded-xl p-4 shadow-lg hover-glow smooth-transition">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 via-blue-500 to-purple-500 flex items-center justify-center shadow-lg ring-2 ring-blue-500/30">
                        <i class="fas fa-user text-white text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm truncate"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-[#1a1a2e] to-[#1f1f3a] rounded-lg px-3 py-2 border border-blue-500/20">
                    <p class="text-xs text-gray-300">ID: <span class="font-mono text-blue-400 font-semibold"><?= substr($ownerid, 0, 12) ?></span></p>
                </div>
            </div>
        </div>

        <!-- Main Navigation -->
        <nav class="flex-1 px-4 py-6">
            <!-- Main Section -->
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 mb-3 px-3 tracking-wider">MAIN</p>
                <a href="?page=home" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'home' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="?page=applications" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'applications' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i class="fas fa-cubes text-purple-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Applications</span>
                </a>
            </div>

            <!-- Applications Section (Dynamic) -->
            <?php if (!empty($applications)): ?>
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 mb-3 px-3 tracking-wider">YOUR APPS</p>
                <div class="space-y-1.5 max-h-64 overflow-y-auto px-2">
                    <?php foreach ($applications as $app): ?>
                    <details class="group">
                        <summary class="sidebar-item cursor-pointer flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-[#262641] text-sm list-none">
                            <i class="fas fa-chevron-right text-gray-400 text-xs group-open:rotate-90 smooth-transition"></i>
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-md">
                                <i class="fas fa-cube text-white text-xs"></i>
                            </div>
                            <span class="truncate font-medium flex-1"><?= htmlspecialchars(substr($app['name'], 0, 16)) ?></span>
                            <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-[10px] rounded-full font-bold"><?= $app['active_keys'] ?></span>
                        </summary>
                        <div class="space-y-0.5 pl-11 mt-1.5 mb-1">
                            <a href="?page=app-overview&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-chart-pie text-blue-400 w-3.5"></i>
                                <span>Overview</span>
                            </a>
                            <a href="?page=licenses&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-key text-yellow-400 w-3.5"></i>
                                <span>Licenses</span>
                            </a>
                            <a href="?page=users&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-users text-green-400 w-3.5"></i>
                                <span>Users</span>
                            </a>
                            <a href="?page=variables&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-sliders-h text-purple-400 w-3.5"></i>
                                <span>Variables</span>
                            </a>
                            <a href="?page=subscriptions&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-credit-card text-pink-400 w-3.5"></i>
                                <span>Subscriptions</span>
                            </a>
                            <a href="?page=files&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-file-download text-cyan-400 w-3.5"></i>
                                <span>Files</span>
                            </a>
                            <a href="?page=webhooks&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-webhook text-orange-400 w-3.5"></i>
                                <span>Webhooks</span>
                            </a>
                            <a href="?page=blacklist&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-ban text-red-400 w-3.5"></i>
                                <span>Blacklist</span>
                            </a>
                            <a href="?page=logs&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-history text-indigo-400 w-3.5"></i>
                                <span>Logs</span>
                            </a>
                            <a href="?page=settings&app=<?= urlencode($app['secret']) ?>" class="submenu-item flex items-center gap-2 px-3 py-1.5 rounded-md text-xs text-gray-300 hover:text-white smooth-transition">
                                <i class="fas fa-cog text-gray-400 w-3.5"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </details>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Account Section -->
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 mb-3 px-3 tracking-wider">ACCOUNT</p>
                <a href="?page=profile" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'profile' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                        <i class="fas fa-user-circle text-indigo-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Profile</span>
                </a>
                <a href="?page=api-keys" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'api-keys' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                        <i class="fas fa-key text-yellow-400 text-sm"></i>
                    </div>
                    <span class="font-medium">API Keys</span>
                </a>
                <a href="?page=billing" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'billing' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center">
                        <i class="fas fa-credit-card text-green-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Billing</span>
                </a>
                <a href="?page=integrations" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'integrations' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-pink-500/10 flex items-center justify-center">
                        <i class="fas fa-plug text-pink-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Integrations</span>
                </a>
            </div>

            <!-- Support Section -->
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 mb-3 px-3 tracking-wider">SUPPORT</p>
                <a href="?page=documentation" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'documentation' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                        <i class="fas fa-book text-cyan-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Documentation</span>
                </a>
                <a href="?page=support" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'support' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center">
                        <i class="fas fa-headset text-orange-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Support</span>
                </a>
            </div>
        </nav>

        <!-- Logout Button -->
        <div class="p-4 border-t border-[#2a2a4e] bg-gradient-to-b from-transparent to-[#15152a]">
            <a href="../logout.php" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 text-red-400 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/50 smooth-transition">
                <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center">
                    <i class="fas fa-sign-out-alt text-red-400 text-sm"></i>
                </div>
                <span class="font-semibold">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header -->
        <header class="bg-gradient-to-r from-[#1a1a2e] via-[#1f1f3a] to-[#1a1a2e] border-b border-[#2a2a4e] h-16 flex items-center px-8 justify-between shadow-lg">
            <div>
                <h2 class="text-xl font-bold capitalize bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent"><?= htmlspecialchars(str_replace('-', ' ', $page)) ?></h2>
                <p class="text-xs text-gray-400 font-medium">Welcome back, <span class="text-blue-400"><?= htmlspecialchars($user['username']) ?></span></p>
            </div>
            <div class="flex items-center gap-4">
                <button class="relative p-2.5 hover:bg-[#262641] rounded-xl transition-all duration-300 hover:scale-110">
                    <i class="fas fa-bell text-gray-300 text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full notification-dot"></span>
                </button>
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 via-blue-500 to-purple-500 flex items-center justify-center shadow-lg ring-2 ring-blue-500/30 hover:ring-blue-500/50 transition-all duration-300 cursor-pointer">
                    <i class="fas fa-user text-white text-base"></i>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
            <?php
                switch ($page) {
                    case 'home':
                        include 'pages/home.php';
                        break;
                    case 'applications':
                        include 'pages/applications.php';
                        break;
                    case 'app-overview':
                        if (!empty($selected_app_data)) {
                            include 'pages/app-overview.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'licenses':
                        if (!empty($selected_app_data)) {
                            include 'pages/licenses.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'users':
                        if (!empty($selected_app_data)) {
                            include 'pages/users.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'variables':
                        if (!empty($selected_app_data)) {
                            include 'pages/variables.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'subscriptions':
                        if (!empty($selected_app_data)) {
                            include 'pages/subscriptions.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'files':
                        if (!empty($selected_app_data)) {
                            include 'pages/files.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'webhooks':
                        if (!empty($selected_app_data)) {
                            include 'pages/webhooks.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'blacklist':
                        if (!empty($selected_app_data)) {
                            include 'pages/blacklist.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'logs':
                        if (!empty($selected_app_data)) {
                            include 'pages/logs.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'settings':
                        if (!empty($selected_app_data)) {
                            include 'pages/settings.php';
                        } else {
                            echo '<div class="text-center py-12"><p class="text-gray-400">App not found</p></div>';
                        }
                        break;
                    case 'profile':
                        include 'pages/profile.php';
                        break;
                    case 'api-keys':
                        include 'pages/api-keys.php';
                        break;
                    case 'billing':
                        include 'pages/billing.php';
                        break;
                    case 'integrations':
                        include 'pages/integrations.php';
                        break;
                    case 'documentation':
                        include 'pages/documentation.php';
                        break;
                    case 'support':
                        include 'pages/support.php';
                        break;
                    default:
                        include 'pages/home.php';
                }
            ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>
