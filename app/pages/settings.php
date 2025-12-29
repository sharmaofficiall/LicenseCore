<?php // Settings Page ?>
<h2 class="text-2xl font-bold mb-8">Application Settings</h2>

<?php if ($app_error): ?>
<div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300 flex items-center gap-3">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= htmlspecialchars($app_error) ?></span>
</div>
<?php endif; ?>

<?php if ($app_success): ?>
<div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-green-300 flex items-center gap-3">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($app_success) ?></span>
</div>
<?php endif; ?>

<div class="space-y-6">
    <!-- General Settings -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">General Settings</h3>
        <form class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Application Name</label>
                <input type="text" value="<?= htmlspecialchars($selected_app_data['name']) ?>" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Version</label>
                <input type="text" value="<?= htmlspecialchars($selected_app_data['version']) ?>" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea rows="3" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500"><?= htmlspecialchars($selected_app_data['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                <i class="fas fa-save mr-2"></i> Save Changes
            </button>
        </form>
    </div>
    
    <!-- Danger Zone -->
    <div class="bg-red-500/10 border border-red-500/50 rounded-lg p-6">
        <h3 class="text-lg font-bold text-red-400 mb-4">Danger Zone</h3>
        <p class="text-sm text-gray-400 mb-4">Once you delete this application, there is no going back. Please be certain.</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this application? This action cannot be undone.')" class="inline">
            <input type="hidden" name="action" value="delete_app">
            <input type="hidden" name="app" value="<?= htmlspecialchars($selected_app) ?>">
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition font-medium">
                <i class="fas fa-trash mr-2"></i> Delete Application
            </button>
        </form>
    </div>
</div>
