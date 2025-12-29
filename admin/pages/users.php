<?php
// Admin User Management Page

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

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
}

// Get users with additional stats
$users_query = $conn->query("
    SELECT
        a.*,
        (SELECT COUNT(*) FROM apps WHERE ownerid = a.ownerid) as app_count,
        (SELECT COUNT(*) FROM `keys` k JOIN apps app ON k.app = app.secret WHERE app.ownerid = a.ownerid AND k.status = 'active') as key_count,
        (SELECT COUNT(*) FROM end_users eu JOIN apps app ON eu.app = app.secret WHERE app.ownerid = a.ownerid) as end_user_count
    FROM accounts a
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

<!-- User Management Table -->
<div class="stat-card rounded-lg">
    <div class="p-6 border-b border-[#2a2a4e]">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">User Management</h3>
            <div class="text-sm text-gray-400">
                Total Users: <span class="text-blue-400 font-semibold"><?= $users_query->num_rows ?></span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f0f1a]">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stats</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php while ($user_data = $users_query->fetch_assoc()): ?>
                <tr class="hover:bg-[#262641] transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center mr-3">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-white"><?= htmlspecialchars($user_data['username']) ?></div>
                                <div class="text-xs text-gray-400"><?= htmlspecialchars($user_data['email']) ?></div>
                                <div class="text-xs text-gray-500 font-mono">ID: <?= htmlspecialchars(substr($user_data['ownerid'], 0, 12)) ?>...</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-indigo-500/20 text-indigo-400 text-xs rounded-full capitalize">
                            <?= htmlspecialchars($user_data['role']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="flex items-center gap-4">
                                <span class="text-purple-400"><i class="fas fa-cube mr-1"></i><?= $user_data['app_count'] ?></span>
                                <span class="text-green-400"><i class="fas fa-key mr-1"></i><?= $user_data['key_count'] ?></span>
                                <span class="text-yellow-400"><i class="fas fa-users mr-1"></i><?= $user_data['end_user_count'] ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($user_data['banned']): ?>
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full">Banned</span>
                            <?php if (!empty($user_data['ban_reason'])): ?>
                                <div class="text-xs text-gray-500 mt-1 max-w-32 truncate" title="<?= htmlspecialchars($user_data['ban_reason']) ?>">
                                    <?= htmlspecialchars($user_data['ban_reason']) ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?= date('M j, Y', strtotime($user_data['created_at'])) ?>
                        <?php if (!empty($user_data['last_login'])): ?>
                            <div class="text-xs text-gray-500">
                                Last login: <?= date('M j, H:i', strtotime($user_data['last_login'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if ($user_data['banned']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="unban_user">
                                    <input type="hidden" name="username" value="<?= htmlspecialchars($user_data['username']) ?>">
                                    <button type="submit" class="px-3 py-1 bg-green-500/20 text-green-400 text-xs rounded hover:bg-green-500/30 transition" onclick="return confirm('Unban this user?')">
                                        <i class="fas fa-user-check mr-1"></i>Unban
                                    </button>
                                </form>
                            <?php else: ?>
                                <button onclick="openBanModal('<?= htmlspecialchars($user_data['username']) ?>')" class="px-3 py-1 bg-red-500/20 text-red-400 text-xs rounded hover:bg-red-500/30 transition">
                                    <i class="fas fa-ban mr-1"></i>Ban
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($users_query->num_rows === 0): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-users text-3xl mb-3"></i>
            <p>No users found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Ban User Modal -->
<div id="banUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">Ban User</h3>
            <button onclick="closeModal('banUserModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ban_user">
            <input type="hidden" name="username" id="banUsername" value="">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Username</label>
                <input type="text" id="banUsernameDisplay" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" readonly>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Ban Reason</label>
                <textarea name="reason" rows="3" class="w-full p-3 bg-[#0f0f1a] border border-[#2a2a4e] rounded-lg text-white" placeholder="Reason for banning this user" required></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('banUserModal')" class="flex-1 bg-gray-500/20 text-gray-400 font-medium py-3 px-4 rounded-lg transition hover:bg-gray-500/30">
                    Cancel
                </button>
                <button type="submit" class="flex-1 bg-red-500/20 text-red-400 font-medium py-3 px-4 rounded-lg transition hover:bg-red-500/30">
                    Ban User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openBanModal(username) {
    document.getElementById('banUsername').value = username;
    document.getElementById('banUsernameDisplay').value = username;
    document.getElementById('banUserModal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = event.target;
    if (modal.classList.contains('bg-black')) {
        modal.classList.add('hidden');
    }
}
</script>
