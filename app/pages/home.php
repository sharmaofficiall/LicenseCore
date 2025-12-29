<?php
// Dashboard Home Page
?>

<!-- Statistics Section -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Apps -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Total Applications</p>
                <p class="text-4xl font-bold mt-2"><?= $stats['total_apps'] ?? 0 ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-blue-500/20 flex items-center justify-center">
                <i class="fas fa-cube text-blue-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-arrow-up text-green-400 mr-1"></i> 12% increase</p>
    </div>

    <!-- Active License Keys -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Active License Keys</p>
                <p class="text-4xl font-bold mt-2"><?= $stats['total_keys'] ?? 0 ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-green-500/20 flex items-center justify-center">
                <i class="fas fa-key text-green-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-arrow-up text-green-400 mr-1"></i> 8% increase</p>
    </div>

    <!-- Total Users -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 hover-glow smooth-transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm font-medium">Total Users</p>
                <p class="text-4xl font-bold mt-2"><?= $stats['total_users'] ?? 0 ?></p>
            </div>
            <div class="w-16 h-16 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <i class="fas fa-users text-purple-400 text-2xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4"><i class="fas fa-arrow-up text-green-400 mr-1"></i> 5% increase</p>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Applications -->
    <div class="lg:col-span-2 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg">
        <div class="p-6 border-b border-[#2a2a4e]">
            <h3 class="text-lg font-bold">Recent Applications</h3>
        </div>
        <div class="divide-y divide-[#2a2a4e]">
            <?php if (!empty($applications)): ?>
                <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                <div class="p-6 hover:bg-[#262641] transition cursor-pointer">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-puzzle-piece text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold"><?= htmlspecialchars($app['name']) ?></h4>
                                <p class="text-xs text-gray-400 mt-1">v<?= htmlspecialchars($app['version']) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-blue-400"><?= $app['active_keys'] ?> Keys</p>
                            <p class="text-xs text-gray-400"><?= $app['total_users'] ?> Users</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-3"></i>
                    <p>No applications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Quick Actions</h3>
        <div class="space-y-3">
            <a href="?page=applications" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-plus mr-2"></i> Create App
            </a>
            <a href="?page=api-keys" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-key mr-2"></i> API Keys
            </a>
            <a href="?page=documentation" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-book mr-2"></i> Documentation
            </a>
            <a href="?page=support" class="block p-4 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-center font-medium">
                <i class="fas fa-headset mr-2"></i> Support
            </a>
        </div>
    </div>
</div>
