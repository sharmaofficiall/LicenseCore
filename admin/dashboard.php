<?php
require_once '../includes/enhanced.php';
require_login();

$user = get_current_user_data();

// Check if user is admin (you might want to add an admin role check here)
// For now, assume all logged in users can access admin dashboard

// Get current page
$page = sanitize($_GET['page'] ?? 'dashboard');

// Handle various admin actions
$action_error = '';
$action_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Create application for a user
    if ($action === 'create_app') {
        $app_name = sanitize($_POST['app_name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $ownerid = sanitize($_POST['ownerid'] ?? '');

        if (empty($app_name) || empty($ownerid)) {
            $action_error = 'Application name and owner are required';
        } else {
            $secret = generate_app_secret();

            $stmt = $conn->prepare("INSERT INTO apps (secret, name, ownerid, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $secret, $app_name, $ownerid, $description);

            if ($stmt->execute()) {
                $action_success = 'Application created successfully!';
                log_action($secret, 'app_created_admin', $user['username'], 'Application created by admin');
            } else {
                $action_error = 'Error creating application';
            }
        }
    }

    // Delete application
    if ($action === 'delete_app') {
        $app_secret = sanitize($_POST['app_secret'] ?? '');

        if (!empty($app_secret)) {
            try {
                $conn->begin_transaction();
                $conn->query("SET FOREIGN_KEY_CHECKS=0");

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

                $stmt = $conn->prepare("DELETE FROM apps WHERE secret = ?");
                $stmt->bind_param("s", $app_secret);
                if ($stmt->execute()) {
                    $conn->commit();
                    $action_success = 'Application deleted successfully';
                    log_action($app_secret, 'app_deleted_admin', $user['username'], 'Application deleted by admin');
                } else {
                    throw new Exception('Failed to delete application');
                }

                $conn->query("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $e) {
                $conn->rollback();
                $conn->query("SET FOREIGN_KEY_CHECKS=1");
                $action_error = 'Failed to delete application: ' . $e->getMessage();
            }
        }
    }

    // Ban/Unban user
    if ($action === 'ban_user') {
        $target_user = sanitize($_POST['username'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 1, ban_reason = ? WHERE username = ?");
            $stmt->bind_param("ss", $reason, $target_user);
            if ($stmt->execute()) {
                $action_success = 'User banned successfully';
                log_action('', 'user_banned', $user['username'], "User $target_user banned: $reason");
            } else {
                $action_error = 'Failed to ban user';
            }
        }
    }

    if ($action === 'unban_user') {
        $target_user = sanitize($_POST['username'] ?? '');

        if (!empty($target_user)) {
            $stmt = $conn->prepare("UPDATE accounts SET banned = 0, ban_reason = NULL WHERE username = ?");
            $stmt->bind_param("s", $target_user);
            if ($stmt->execute()) {
                $action_success = 'User unbanned successfully';
                log_action('', 'user_unbanned', $user['username'], "User $target_user unbanned");
            } else {
                $action_error = 'Failed to unban user';
            }
        }
    }

    // Delete license key
    if ($action === 'delete_key') {
        $key_id = intval($_POST['key_id'] ?? 0);

        if ($key_id > 0) {
            $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
            $stmt->bind_param("i", $key_id);
            if ($stmt->execute()) {
                $action_success = 'License key deleted successfully';
                log_action('', 'key_deleted_admin', $user['username'], 'License key deleted by admin');
            } else {
                $action_error = 'Failed to delete license key';
            }
        }
    }
}

// Get system statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM accounts")->fetch_assoc()['count'],
    'total_apps' => $conn->query("SELECT COUNT(*) as count FROM apps")->fetch_assoc()['count'],
    'total_keys' => $conn->query("SELECT COUNT(*) as count FROM `keys` WHERE status = 'active'")->fetch_assoc()['count'],
    'total_end_users' => $conn->query("SELECT COUNT(*) as count FROM end_users")->fetch_assoc()['count'],
    'banned_users' => $conn->query("SELECT COUNT(*) as count FROM accounts WHERE banned = 1")->fetch_assoc()['count'],
    'recent_logs' => $conn->query("SELECT COUNT(*) as count FROM logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count']
];

// Get recent applications
$recent_apps = $conn->query("SELECT a.*, acc.username as owner_name FROM apps a JOIN accounts acc ON a.ownerid = acc.ownerid ORDER BY a.created_at DESC LIMIT 5");

// Get recent users
$recent_users = $conn->query("SELECT * FROM accounts ORDER BY created_at DESC LIMIT 5");

// Get all applications for management
$all_apps = $conn->query("SELECT a.*, acc.username as owner_name, 
    (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'active') as active_keys,
    (SELECT COUNT(*) FROM end_users WHERE app = a.secret) as total_users
    FROM apps a JOIN accounts acc ON a.ownerid = acc.ownerid ORDER BY a.created_at DESC");

// Get all users for management
$all_users = $conn->query("SELECT * FROM accounts ORDER BY created_at DESC");

// Get recent license keys
$recent_keys = $conn->query("SELECT k.*, a.name as app_name FROM `keys` k JOIN apps a ON k.app = a.secret ORDER BY k.created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LicenseCore</title>
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
        .stat-card {
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.1);
            backdrop-filter: blur(10px);
        }
        .data-table {
            background: rgba(26, 26, 46, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.1);
            backdrop-filter: blur(10px);
        }
        .data-table th {
            background: rgba(59, 130, 246, 0.1);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }
        .data-table td {
            border-bottom: 1px solid rgba(59, 130, 246, 0.05);
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
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 via-red-600 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-xl gradient-text">Admin Panel</h1>
                    <p class="text-xs text-gray-400 font-medium">LicenseCore v2.0</p>
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-[#2a2a4e]">
            <div class="glass-effect rounded-xl p-4 shadow-lg hover-glow smooth-transition">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-400 via-red-500 to-pink-500 flex items-center justify-center shadow-lg ring-2 ring-red-500/30">
                        <i class="fas fa-user-shield text-white text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm truncate"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-xs text-gray-400 truncate">Administrator</p>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-[#1a1a2e] to-[#1f1f3a] rounded-lg px-3 py-2 border border-red-500/20">
                    <p class="text-xs text-gray-300">Role: <span class="font-mono text-red-400 font-semibold">ADMIN</span></p>
                </div>
            </div>
        </div>

        <!-- Main Navigation -->
        <nav class="flex-1 px-4 py-6">
            <!-- Main Section -->
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-500 mb-3 px-3 tracking-wider">ADMIN PANEL</p>
                <a href="?page=dashboard" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'dashboard' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                        <i class="fas fa-tachometer-alt text-blue-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="?page=users" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'users' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center">
                        <i class="fas fa-users text-green-400 text-sm"></i>
                    </div>
                    <span class="font-medium">User Management</span>
                </a>
                <a href="?page=applications" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'applications' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i class="fas fa-cubes text-purple-400 text-sm"></i>
                    </div>
                    <span class="font-medium">Applications</span>
                </a>
                <a href="?page=licenses" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'licenses' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                        <i class="fas fa-key text-yellow-400 text-sm"></i>
                    </div>
                    <span class="font-medium">License Keys</span>
                </a>
                <a href="?page=logs" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 <?= $page === 'logs' ? 'sidebar-active' : 'hover:bg-[#262641]' ?>">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                        <i class="fas fa-history text-indigo-400 text-sm"></i>
                    </div>
                    <span class="font-medium">System Logs</span>
                </a>
            </div>
        </nav>

        <!-- Logout Button -->
        <div class="p-4 border-t border-[#2a2a4e] bg-gradient-to-b from-transparent to-[#15152a]">
            <a href="logout.php" class="sidebar-item flex items-center gap-3 px-4 py-2.5 rounded-lg mx-2 text-red-400 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/50 smooth-transition">
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
                <h2 class="text-xl font-bold capitalize bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                    <?php
                    switch($page) {
                        case 'dashboard': echo 'Admin Dashboard'; break;
                        case 'users': echo 'User Management'; break;
                        case 'applications': echo 'Application Management'; break;
                        case 'licenses': echo 'License Management'; break;
                        case 'logs': echo 'System Logs'; break;
                        default: echo 'Admin Dashboard';
                    }
                    ?>
                </h2>
                <p class="text-xs text-gray-400 font-medium">Welcome back, <span class="text-red-400"><?= htmlspecialchars($user['username']) ?></span></p>
            </div>
            <div class="flex items-center gap-4">
                <button class="relative p-2.5 hover:bg-[#262641] rounded-xl transition-all duration-300 hover:scale-110">
                    <i class="fas fa-bell text-gray-300 text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full notification-dot"></span>
                </button>
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-400 via-red-500 to-pink-500 flex items-center justify-center shadow-lg ring-2 ring-red-500/30 hover:ring-red-500/50 transition-all duration-300 cursor-pointer">
                    <i class="fas fa-user-shield text-white text-base"></i>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
            <?php
                switch ($page) {
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'users':
                        include 'pages/users.php';
                        break;
                    case 'applications':
                        include 'pages/applications.php';
                        break;
                    case 'licenses':
                        include 'pages/licenses.php';
                        break;
                    case 'logs':
                        include 'pages/logs.php';
                        break;
                    default:
                        include 'pages/dashboard.php';
                }
            ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>
