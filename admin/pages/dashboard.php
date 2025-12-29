<?php
// Admin Dashboard Home Page
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

<!-- Statistics Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users -->
    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Total Users</p>
                <p class="text-3xl font-bold mt-2 text-blue-400"><?= number_format($stats['total_users']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <i class="fas fa-users text-blue-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4">
            <i class="fas fa-user-shield text-red-400 mr-1"></i>
            <?= number_format($stats['banned_users']) ?> banned
        </p>
    </div>

    <!-- Total Applications -->
    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Total Applications</p>
                <p class="text-3xl font-bold mt-2 text-purple-400"><?= number_format($stats['total_apps']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <i class="fas fa-cubes text-purple-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-arrow-up text-green-400 mr-1"></i> Active systems</p>
    </div>

    <!-- Active License Keys -->
    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Active Licenses</p>
                <p class="text-3xl font-bold mt-2 text-green-400"><?= number_format($stats['total_keys']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-green-500/20 flex items-center justify-center">
                <i class="fas fa-key text-green-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-arrow-up text-green-400 mr-1"></i> Valid keys</p>
    </div>

    <!-- Total End Users -->
    <div class="stat-card rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">End Users</p>
                <p class="text-3xl font-bold mt-2 text-yellow-400"><?= number_format($stats['total_end_users']) ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                <i class="fas fa-user-friends text-yellow-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-clock text-blue-400 mr-1"></i> Last 24h: <?= number_format($stats['recent_logs']) ?> logs</p>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Applications -->
    <div class="lg:col-span-2 stat-card rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Recent Applications</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if ($recent_apps->num_rows > 0): ?>
                <?php while ($app = $recent_apps->fetch_assoc()): ?>
                <div class="p-6 hover:bg-[#262641] transition cursor-pointer">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-cube text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold"><?= htmlspecialchars($app['name']) ?></h4>
                                <p class="text-xs text-gray-400 mt-1">Owner: <?= htmlspecialchars($app['owner_name']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-blue-400"><?= htmlspecialchars(substr($app['secret'], 0, 12)) ?>...</p>
                            <p class="text-xs text-gray-400"><?= date('M j, H:i', strtotime($app['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-3"></i>
                    <p>No applications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="stat-card rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Quick Actions</h3>
        <div class="space-y-3">
            <button onclick="openModal('createAppModal')" class="w-full p-4 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 rounded-lg transition text-center font-medium text-white">
                <i class="fas fa-plus mr-2"></i> Create App
            </button>
            <a href="?page=users" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-users mr-2"></i> Manage Users
            </a>
            <a href="?page=applications" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-cubes mr-2"></i> View Apps
            </a>
            <a href="?page=logs" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-history mr-2"></i> System Logs
            </a>
        </div>
    </div>
</div>

<!-- Recent Users & License Keys -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Recent Users -->
    <div class="stat-card rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Recent Users</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if ($recent_users->num_rows > 0): ?>
                <?php while ($user_data = $recent_users->fetch_assoc()): ?>
                <div class="p-4 hover:bg-[#262641] transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-sm"><?= htmlspecialchars($user_data['username']) ?></p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($user_data['email']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php if ($user_data['banned']): ?>
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full">Banned</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-users text-3xl mb-3"></i>
                    <p>No users yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent License Keys -->
    <div class="stat-card rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Recent License Keys</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if ($recent_keys->num_rows > 0): ?>
                <?php while ($key = $recent_keys->fetch_assoc()): ?>
                <div class="p-4 hover:bg-[#262641] transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                                <i class="fas fa-key text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-mono text-sm font-semibold"><?= htmlspecialchars(substr($key['license_key'], 0, 20)) ?>...</p>
                                <p class="text-xs text-gray-400"><?= htmlspecialchars($key['app_name']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 bg-<?= $key['status'] === 'active' ? 'green' : 'red' ?>-500/20 text-<?= $key['status'] === 'active' ? 'green' : 'red' ?>-400 text-xs rounded-full capitalize">
                                <?= htmlspecialchars($key['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-key text-3xl mb-3"></i>
                    <p>No license keys yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
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

<script>
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
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
