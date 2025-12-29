<?php
// Admin License Management Page

// Handle license actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

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

// Get license keys with app and user info
$licenses_query = $conn->query("
    SELECT
        k.*,
        a.name as app_name,
        a.secret as app_secret,
        acc.username as owner_name,
        eu.username as used_by_username
    FROM `keys` k
    JOIN apps a ON k.app = a.secret
    JOIN accounts acc ON a.ownerid = acc.ownerid
    LEFT JOIN end_users eu ON k.used_by = eu.username AND eu.app = k.app
    ORDER BY k.created_at DESC
    LIMIT 100
");

// Get license statistics
$license_stats = $conn->query("
    SELECT
        COUNT(*) as total_keys,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_keys,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_keys,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_keys,
        SUM(CASE WHEN expiry > UNIX_TIMESTAMP() THEN 1 ELSE 0 END) as valid_keys,
        SUM(CASE WHEN expiry <= UNIX_TIMESTAMP() AND expiry > 0 THEN 1 ELSE 0 END) as expired_count
    FROM `keys`
")->fetch_assoc();
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

<!-- License Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Total Keys</p>
            <p class="text-2xl font-bold mt-1 text-blue-400"><?= number_format($license_stats['total_keys']) ?></p>
        </div>
    </div>
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Active</p>
            <p class="text-2xl font-bold mt-1 text-green-400"><?= number_format($license_stats['active_keys']) ?></p>
        </div>
    </div>
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Inactive</p>
            <p class="text-2xl font-bold mt-1 text-gray-400"><?= number_format($license_stats['inactive_keys']) ?></p>
        </div>
    </div>
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Expired</p>
            <p class="text-2xl font-bold mt-1 text-red-400"><?= number_format($license_stats['expired_keys']) ?></p>
        </div>
    </div>
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Valid</p>
            <p class="text-2xl font-bold mt-1 text-yellow-400"><?= number_format($license_stats['valid_keys']) ?></p>
        </div>
    </div>
    <div class="stat-card rounded-lg p-4 hover-glow smooth-transition">
        <div class="text-center">
            <p class="text-gray-400 text-sm font-medium">Expired</p>
            <p class="text-2xl font-bold mt-1 text-red-400"><?= number_format($license_stats['expired_count']) ?></p>
        </div>
    </div>
</div>

<!-- License Management Table -->
<div class="stat-card rounded-lg">
    <div class="p-6 border-b border-[#2a2a4e]">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold">License Key Management</h3>
            <div class="text-sm text-gray-400">
                Showing latest 100 keys
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#0f0f1a]">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">License Key</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Application</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Expiry</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Used By</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php while ($key = $licenses_query->fetch_assoc()): ?>
                <tr class="hover:bg-[#262641] transition">
                    <td class="px-6 py-4">
                        <div class="font-mono text-sm text-blue-400">
                            <?= htmlspecialchars(substr($key['license_key'], 0, 24)) ?>...
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Format: <?= htmlspecialchars($key['format']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-semibold text-purple-400"><?= htmlspecialchars($key['app_name']) ?></div>
                            <div class="text-xs text-gray-500 font-mono">Secret: <?= htmlspecialchars(substr($key['app_secret'], 0, 12)) ?>...</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-semibold text-green-400"><?= htmlspecialchars($key['owner_name']) ?></div>
                            <?php if (!empty($key['created_by'])): ?>
                                <div class="text-xs text-gray-500">Created by: <?= htmlspecialchars($key['created_by']) ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $status_class = 'bg-gray-500/20 text-gray-400';
                        if ($key['status'] === 'active') $status_class = 'bg-green-500/20 text-green-400';
                        elseif ($key['status'] === 'inactive') $status_class = 'bg-yellow-500/20 text-yellow-400';
                        elseif ($key['status'] === 'expired') $status_class = 'bg-red-500/20 text-red-400';
                        ?>
                        <span class="px-2 py-1 <?= $status_class ?> text-xs rounded-full capitalize">
                            <?= htmlspecialchars($key['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?php if ($key['expiry'] == 0): ?>
                            <span class="text-gray-500">Never</span>
                        <?php elseif ($key['expiry'] <= time()): ?>
                            <span class="text-red-400">Expired</span>
                            <div class="text-xs text-gray-500">
                                <?= date('M j, Y H:i', $key['expiry']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-green-400">Active</span>
                            <div class="text-xs text-gray-500">
                                <?= date('M j, Y H:i', $key['expiry']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?php if (!empty($key['used_by'])): ?>
                            <div class="text-yellow-400"><?= htmlspecialchars($key['used_by']) ?></div>
                            <?php if (!empty($key['used_at'])): ?>
                                <div class="text-xs text-gray-500">
                                    <?= date('M j, H:i', strtotime($key['used_at'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-500">Not used</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-400">
                        <?= date('M j, Y', strtotime($key['created_at'])) ?>
                        <div class="text-xs text-gray-500">
                            <?= date('H:i', strtotime($key['created_at'])) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="viewKeyDetails(<?= $key['id'] ?>)" class="px-3 py-1 bg-blue-500/20 text-blue-400 text-xs rounded hover:bg-blue-500/30 transition" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                <button type="submit" class="px-3 py-1 bg-red-500/20 text-red-400 text-xs rounded hover:bg-red-500/30 transition" onclick="return confirm('Delete this license key?')" title="Delete Key">
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

    <?php if ($licenses_query->num_rows === 0): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-key text-3xl mb-3"></i>
            <p>No license keys found</p>
        </div>
    <?php endif; ?>
</div>

<!-- Key Details Modal -->
<div id="keyDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 w-full max-w-lg mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">License Key Details</h3>
            <button onclick="closeModal('keyDetailsModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="keyDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function viewKeyDetails(keyId) {
    // For now, just show a simple alert. In a real implementation, you'd fetch details via AJAX
    alert('Key ID: ' + keyId + '\n\nDetailed view would show comprehensive license key information, usage history, and settings.');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = event.target;
    if (modal.classList.contains('bg-black')) {
        modal.classList.add('hidden');
    }
}
</script>
