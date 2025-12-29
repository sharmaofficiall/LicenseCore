<?php // Profile Page ?>
<h2 class="text-2xl font-bold mb-8">My Profile</h2>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <div class="text-center mb-6">
            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center mb-4">
                <i class="fas fa-user text-white text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold"><?= htmlspecialchars($user['username']) ?></h3>
            <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($user['email']) ?></p>
        </div>
        
        <div class="space-y-3">
            <div class="bg-[#262641] rounded-lg p-3">
                <p class="text-xs text-gray-400">Account ID</p>
                <p class="font-mono text-sm mt-1"><?= substr($ownerid, 0, 16) ?>...</p>
            </div>
            <div class="bg-[#262641] rounded-lg p-3">
                <p class="text-xs text-gray-400">Member Since</p>
                <p class="text-sm mt-1"><?= date('M d, Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile -->
    <div class="lg:col-span-2 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Edit Profile</h3>
        
        <form class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
            </div>
            
            <div class="border-t border-[#2a2a4e] pt-6">
                <h4 class="font-semibold mb-4">Change Password</h4>
                <div class="space-y-4">
                    <input type="password" placeholder="Current Password" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                    <input type="password" placeholder="New Password" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                    <input type="password" placeholder="Confirm New Password" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                </div>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                <i class="fas fa-save mr-2"></i> Save Changes
            </button>
        </form>
    </div>
</div>
