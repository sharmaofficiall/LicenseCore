<?php
// Admin Application Management Page

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

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
}

// Get applications with stats
$apps_query = $conn->query("
    SELECT
        a.*,
        acc.username as owner_name,
        acc.email as owner_email,
        (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'active') as active_keys,
        (SELECT COUNT(*) FROM `keys` WHERE app = a.secret AND status = 'inactive') as inactive_keys,
        (SELECT COUNT(*) FROM end_users WHERE app = a.secret) as total_users,
        (SELECT COUNT(*) FROM logs WHERE app = a.secret) as total_logs
    FROM apps a
    JOIN accounts acc ON a.ownerid = acc.ownerid
    ORDER BY a.created_at DESC
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

<!-- Application Management Table -->
<div class="stat-card rounded-lg">
    <div class="p-6 border-b border-[#2a2a4e]">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">Application Management</h3>
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-400">
                    Total Apps: <span class="text-purple-400 font-semibold"><?= $apps_query->num_rows ?></span>
                </div>
                <button onclick="openModal('createAppModal')" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Create App
                </button>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f0f1a]">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Application</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stats</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php while ($app = $apps_query->fetch_assoc()): ?>
                <tr class="hover:bg-[#262641] transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mr-3">
                                <i class="fas fa-cube text-white"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-white"><?= htmlspecialchars($app['name']) ?></div>
                                <div class="text-xs text-gray-400">v<?= htmlspecialchars($app['version']) ?></div>
                                <div class="text-xs text-gray-500 font-mono">Secret: <?= htmlspecialchars(substr($app['secret'], 0, 16)) ?>...</div>
                                <?php if (!empty($app['description'])): ?>
                                    <div class="text-xs text-gray-500 mt-1 max-w-64 truncate" title="<?= htmlspecialchars($app['description']) ?>">
                                        <?= htmlspecialchars($app['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-semibold text-blue-400"><?= htmlspecialchars($app['owner_name']) ?></div>
                            <div class="text-xs text-gray-400"><?= htmlspecialchars($app['owner_email']) ?></div>
                            <div class="text-xs text-gray-500 font-mono">ID: <?= htmlspecialchars(substr($app['ownerid'], 0, 12)) ?>...</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm space-y-1">
                            <div class="flex items-center gap-4">
                                <span class="text-green-400"><i class="fas fa-key mr-1"></i><?= $app['active_keys'] ?> active</span>
                                <span class="text-gray-400"><i class="fas fa-key mr-1"></i><?= $app['inactive_keys'] ?> inactive</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-yellow-400"><i class="fas fa-users mr-1"></i><?= $app['total_users'] ?> users</span>
                                <span class="text-indigo-400"><i class="fas fa-history mr-1"></i><?= $app['total_logs'] ?> logs</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($app['paused']): ?>
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full">Paused</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?= date('M j, Y', strtotime($app['created_at'])) ?>
                        <div class="text-xs text-gray-500">
                            <?= date('H:i', strtotime($app['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="viewAppDetails('<?= htmlspecialchars($app['secret']) ?>')" class="px-3 py-1 bg-blue-500/20 text-blue-400 text-xs rounded hover:bg-blue-500/30 transition" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete_app">
                                <input type="hidden" name="app_secret" value="<?= htmlspecialchars($app['secret']) ?>">
                                <button type="submit" class="px-3 py-1 bg-red-500/20 text-red-400 text-xs rounded hover:bg-red-500/30 transition" onclick="return confirm('Delete this application and all associated data? This cannot be undone!')" title="Delete App">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($apps_query->num_rows === 0): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-cubes text-3xl mb-3"></i>
            <p>No applications found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Create App Modal -->
<div id="createAppModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">Create New Application</h3>
            <button onclick="closeModal('createAppModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_app">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Application Name*</label>
                <input type="text" name="app_name" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" placeholder="My Awesome App" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Owner ID*</label>
                <input type="text" name="ownerid" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" placeholder="Owner ID" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Version</label>
                <input type="text" name="version" value="1.0" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" placeholder="1.0">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" placeholder="Describe your application"></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-medium py-3 px-4 rounded-lg transition">
                Create Application
            </button>
        </form>
    </div>
</div>

<!-- App Details Modal -->
<div id="appDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">Application Details</h3>
            <button onclick="closeModal('appDetailsModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="appDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function viewAppDetails(appSecret) {
    // For now, just show a simple alert. In a real implementation, you'd fetch details via AJAX
    alert('App Secret: ' + appSecret + '\n\nDetailed view would show comprehensive app information, settings, and statistics.');
    // document.getElementById('appDetailsModal').classList.remove('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = event.target;
    if (modal.classList.contains('bg-black')) {
        modal.classList.add('hidden');
    }
}
</script>
