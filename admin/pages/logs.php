<?php
// Admin System Logs Page

// Get logs with pagination
$page = max(1, intval($_GET['p'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total logs count
$total_logs = $conn->query("SELECT COUNT(*) as count FROM logs")->fetch_assoc()['count'];
$total_pages = ceil($total_logs / $per_page);

// Get logs with app and user info
$logs_query = $conn->query("
    SELECT
        l.*,
        a.name as app_name,
        acc.username as user_username
    FROM logs l
    LEFT JOIN apps a ON l.app = a.secret
    LEFT JOIN accounts acc ON l.user = acc.username
    ORDER BY l.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// Get log statistics
$log_stats = $conn->query("
    SELECT
        COUNT(*) as total_logs,
        COUNT(DISTINCT app) as unique_apps,
        COUNT(DISTINCT user) as unique_users,
        COUNT(DISTINCT ip) as unique_ips,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 ELSE 0 END) as logs_last_hour,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as logs_last_24h
    FROM logs
")->fetch_assoc();

// Get most active users
$active_users = $conn->query("
    SELECT user, COUNT(*) as log_count
    FROM logs
    WHERE user IS NOT NULL AND user != ''
    GROUP BY user
    ORDER BY log_count DESC
    LIMIT 10
");

// Get most active apps
$active_apps = $conn->query("
    SELECT a.name, COUNT(l.id) as log_count
    FROM logs l
    LEFT JOIN apps a ON l.app = a.secret
    WHERE l.app IS NOT NULL
    GROUP BY l.app, a.name
    ORDER BY log_count DESC
    LIMIT 10
");
?>

<!-- Success/Error Messages -->
<?php if (!empty($action_error)): ?>
<div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
    <div class="flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-red-400"></i>
        <p class="text-red-300 font-medium"><?= htmlspecialchars($action_error) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($action_success)): ?>
<div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
    <div class="flex items-center gap-3">
        <i class="fas fa-check-circle text-green-400"></i>
        <p class="text-green-300 font-medium"><?= htmlspecialchars($action_success) ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Log Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Total Logs</p>
                <p class="text-3xl font-bold mt-2 text-blue-400"><?= number_format($log_stats['total_logs']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <i class="fas fa-history text-blue-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-clock text-green-400 mr-1"></i> Last 24h: <?= number_format($log_stats['logs_last_24h']) ?></p>
    </div>

    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Active Apps</p>
                <p class="text-3xl font-bold mt-2 text-purple-400"><?= number_format($log_stats['unique_apps']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <i class="fas fa-cubes text-purple-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-users text-blue-400 mr-1"></i> With activity</p>
    </div>

    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Active Users</p>
                <p class="text-3xl font-bold mt-2 text-green-400"><?= number_format($log_stats['unique_users']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-green-500/20 flex items-center justify-center">
                <i class="fas fa-user text-green-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-clock text-yellow-400 mr-1"></i> Last hour: <?= number_format($log_stats['logs_last_hour']) ?></p>
    </div>

    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Unique IPs</p>
                <p class="text-3xl font-bold mt-2 text-yellow-400"><?= number_format($log_stats['unique_ips']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                <i class="fas fa-globe text-yellow-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-network-wired text-indigo-400 mr-1"></i> Different sources</p>
    </div>
</div>

<!-- Top Active Users & Apps -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Most Active Users -->
    <div class="stat-card rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Most Active Users</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if ($active_users->num_rows > 0): ?>
                <?php while ($user = $active_users->fetch_assoc()): ?>
                <div class="p-4 hover:bg-[#262641] transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm text-white"><?= htmlspecialchars($user['user']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-full">
                                <?= number_format($user['log_count']) ?> logs
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-users text-2xl mb-2"></i>
                    <p>No user activity yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Most Active Apps -->
    <div class="stat-card rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Most Active Applications</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if ($active_apps->num_rows > 0): ?>
                <?php while ($app = $active_apps->fetch_assoc()): ?>
                <div class="p-4 hover:bg-[#262641] transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center">
                                <i class="fas fa-cube text-white text-xs"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm text-white"><?= htmlspecialchars($app['name'] ?: 'Unknown App') ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-purple-500/20 text-purple-400 text-xs rounded-full">
                                <?= number_format($app['log_count']) ?> logs
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-cubes text-2xl mb-2"></i>
                    <p>No app activity yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- System Logs Table -->
<div class="stat-card rounded-lg">
    <div class="p-6 border-b border-[#2a2a4e]">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">System Logs</h3>
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-400">
                    Page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_logs) ?> total)
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=logs&p=<?= $page - 1 ?>" class="px-3 py-1 bg-[#262641] hover:bg-[#2a2a4e] text-white text-sm rounded transition">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=logs&p=<?= $page + 1 ?>" class="px-3 py-1 bg-[#262641] hover:bg-[#2a2a4e] text-white text-sm rounded transition">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f0f1a]">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Application</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">IP Address</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php while ($log = $logs_query->fetch_assoc()): ?>
                <tr class="hover:bg-[#262641] transition">
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                        <div class="text-xs text-gray-500">
                            <?= date('H:i:s', strtotime($log['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-indigo-500/20 text-indigo-400 text-xs rounded-full">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-300">
                        <?= htmlspecialchars($log['user'] ?: 'System') ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if (!empty($log['app_name'])): ?>
                            <span class="text-purple-400"><?= htmlspecialchars($log['app_name']) ?></span>
                        <?php else: ?>
                            <span class="text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-mono text-gray-400">
                        <?= htmlspecialchars($log['ip']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-300 max-w-md">
                        <div class="truncate" title="<?= htmlspecialchars($log['message']) ?>">
                            <?= htmlspecialchars($log['message']) ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($logs_query->num_rows === 0): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-history text-3xl mb-3"></i>
            <p>No logs found</p>
        </div>
    <?php endif; ?>
</div>
