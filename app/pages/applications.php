<?php
// Applications Management Page
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Manage Applications</h2>
    <a href="?page=applications&create=true" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium flex items-center gap-2">
        <i class="fas fa-plus"></i> New Application
    </a>
</div>

<?php if ($create_error): ?>
<div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300 flex items-center gap-3">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= htmlspecialchars($create_error) ?></span>
</div>
<?php endif; ?>

<?php if ($create_success): ?>
<div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-300 flex items-center gap-3">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($create_success) ?></span>
</div>
<?php endif; ?>

<?php if (isset($_GET['create']) && $_GET['create'] === 'true'): ?>
<!-- Create Application Form -->
<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-8 mb-8">
    <h3 class="text-xl font-bold mb-6">Create New Application</h3>
    
    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="create_app">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Application Name *</label>
                <input 
                    type="text" 
                    name="app_name" 
                    placeholder="My Awesome Application"
                    required
                    class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Version</label>
                <input 
                    type="text" 
                    name="version" 
                    placeholder="1.0.0"
                    value="1.0"
                    class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea 
                name="description" 
                placeholder="Describe your application..."
                rows="4"
                class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-none"
            ></textarea>
        </div>
        
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium flex items-center gap-2">
                <i class="fas fa-check"></i> Create Application
            </button>
            <a href="?page=applications" class="px-6 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Applications Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($applications)): ?>
        <div class="lg:col-span-3 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-12 text-center">
            <i class="fas fa-inbox text-4xl text-gray-600 mb-4"></i>
            <p class="text-gray-400 mb-6">No applications created yet.</p>
            <a href="?page=applications&create=true" class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                Create Your First App
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($applications as $app): ?>
        <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg overflow-hidden hover:border-blue-500/50 transition group">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 p-6 border-b border-[#2a2a4e]">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-bold text-lg"><?= htmlspecialchars($app['name']) ?></h4>
                        <p class="text-xs text-gray-400 mt-1">v<?= htmlspecialchars($app['version']) ?></p>
                    </div>
                    <span class="px-3 py-1 bg-green-500/20 text-green-400 text-xs rounded-full font-medium">Active</span>
                </div>
            </div>
            
            <!-- Body -->
            <div class="p-6">
                <p class="text-sm text-gray-400 mb-6 line-clamp-2">
                    <?= htmlspecialchars($app['description'] ?? 'No description') ?>
                </p>
                
                <!-- Statistics -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-[#262641] rounded-lg p-4">
                        <p class="text-xs text-gray-400 font-medium">License Keys</p>
                        <p class="text-2xl font-bold mt-2"><?= $app['active_keys'] ?></p>
                    </div>
                    <div class="bg-[#262641] rounded-lg p-4">
                        <p class="text-xs text-gray-400 font-medium">Users</p>
                        <p class="text-2xl font-bold mt-2"><?= $app['total_users'] ?></p>
                    </div>
                </div>
                
                <!-- App Secret -->
                <div class="mb-6 p-3 bg-[#262641] rounded text-xs font-mono text-gray-300 truncate">
                    <?= htmlspecialchars(substr($app['secret'], 0, 64)) ?>...
                </div>
                
                <!-- Actions -->
                <div class="space-y-2">
                    <a href="?page=app-overview&app=<?= urlencode($app['secret']) ?>" class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium text-sm">
                        <i class="fas fa-chart-pie mr-2"></i> View Details
                    </a>
                    <a href="?page=licenses&app=<?= urlencode($app['secret']) ?>" class="block w-full text-center px-4 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition font-medium text-sm">
                        <i class="fas fa-certificate mr-2"></i> Licenses
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
