<?php
// App Overview Page
?>

<?php if (!empty($app_success)): ?>
<div class="mb-6 p-4 bg-green-500/15 border border-green-500/40 rounded-lg text-green-200 flex items-center gap-3">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($app_success) ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto text-green-300 hover:text-green-100">&times;</button>
</div>
<?php endif; ?>

<?php if (!empty($app_error)): ?>
<div class="mb-6 p-4 bg-red-500/15 border border-red-500/40 rounded-lg text-red-200 flex items-center gap-3">
    <i class="fas fa-exclamation-triangle"></i>
    <span><?= htmlspecialchars($app_error) ?></span>
    <button onclick="this.parentElement.remove()" class="ml-auto text-red-300 hover:text-red-100">&times;</button>
</div>
<?php endif; ?>

<div class="mb-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
            <i class="fas fa-cube text-white text-2xl"></i>
        </div>
        <div>
            <h2 class="text-3xl font-bold"><?= htmlspecialchars($selected_app_data['name']) ?></h2>
            <p class="text-gray-400">Version <?= htmlspecialchars($selected_app_data['version']) ?></p>
        </div>
    </div>
</div>

<!-- App Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <?php
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `keys` WHERE app = ? AND status = 'active'");
        $stmt->bind_param("s", $selected_app_data['secret']);
        $stmt->execute();
        $active_keys = $stmt->get_result()->fetch_assoc()['count'];

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `end_users` WHERE app = ?");
        $stmt->bind_param("s", $selected_app_data['secret']);
        $stmt->execute();
        $total_users = $stmt->get_result()->fetch_assoc()['count'];
    ?>
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <p class="text-gray-400 text-sm">Active Keys</p>
        <p class="text-3xl font-bold mt-2"><?= $active_keys ?></p>
    </div>
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <p class="text-gray-400 text-sm">Total Users</p>
        <p class="text-3xl font-bold mt-2"><?= $total_users ?></p>
    </div>
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <p class="text-gray-400 text-sm">Created</p>
        <p class="text-lg font-bold mt-2"><?= date('M d, Y', strtotime($selected_app_data['created_at'])) ?></p>
    </div>
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <p class="text-gray-400 text-sm">Status</p>
        <p class="text-lg font-bold mt-2"><span class="text-green-400">●</span> Active</p>
    </div>
</div>

<!-- App Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Details -->
    <div class="lg:col-span-2 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Application Details</h3>
        
        <div class="space-y-4">
            <div>
                <label class="text-sm text-gray-400">App Secret</label>
                <div class="mt-1 p-3 bg-[#262641] rounded font-mono text-sm text-gray-300 truncate">
                    <?= htmlspecialchars($selected_app_data['secret']) ?>
                </div>
            </div>

            <div class="p-4 bg-[#262641] border border-[#3a3a5e] rounded-lg flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-200">Reset App Secret</p>
                        <p class="text-xs text-gray-400">Rotate the secret to invalidate old clients. You must update all clients with the new secret.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Reset secret? Existing clients must be updated.');">
                        <input type="hidden" name="action" value="reset_secret">
                        <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app_data['secret']) ?>">
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition">
                            <i class="fas fa-rotate mr-2"></i>Reset Secret
                        </button>
                    </form>
                </div>
            </div>
            
            <div>
                <label class="text-sm text-gray-400">Description</label>
                <p class="mt-1 text-gray-300">
                    <?= htmlspecialchars($selected_app_data['description'] ?? 'No description') ?>
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-400">Created At</label>
                    <p class="mt-1 text-gray-300"><?= date('M d, Y H:i', strtotime($selected_app_data['created_at'])) ?></p>
                </div>
                <div>
                    <label class="text-sm text-gray-400">Last Updated</label>
                    <p class="mt-1 text-gray-300"><?= date('M d, Y H:i', strtotime($selected_app_data['modified_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Manage</h3>
        
        <div class="space-y-3">
            <a href="?page=licenses&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-certificate text-blue-400 mr-2"></i> License Keys
            </a>
            <a href="?page=users&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-users text-green-400 mr-2"></i> Users
            </a>
            <a href="?page=variables&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-sliders-h text-purple-400 mr-2"></i> Variables
            </a>
            <a href="?page=webhooks&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-webhook text-orange-400 mr-2"></i> Webhooks
            </a>
            <a href="?page=logs&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-history text-yellow-400 mr-2"></i> Logs
            </a>
            <a href="?page=settings&app=<?= urlencode($selected_app_data['secret']) ?>" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition">
                <i class="fas fa-cog text-gray-400 mr-2"></i> Settings
            </a>
        </div>
    </div>
</div>
