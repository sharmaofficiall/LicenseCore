<?php
// Users Management Page
$app_secret = $selected_app_data['secret'];

// Get all users for this app
$stmt = $conn->prepare("SELECT * FROM `end_users` WHERE app = ? ORDER BY created_at DESC LIMIT 100");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$users_result = $stmt->get_result();
$users = [];

while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}
?>

<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Manage Users</h2>
    <input type="text" placeholder="Search users..." class="px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
</div>

<!-- Users Table -->
<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Username</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">HWID</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">IP Address</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Joined</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-inbox text-3xl mb-3 block"></i>
                        No users yet
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user_item): ?>
                    <tr class="hover:bg-[#262641] transition">
                        <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($user_item['username']) ?></td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-400"><?= htmlspecialchars(substr($user_item['hwid'] ?? 'N/A', 0, 12)) ?>...</td>
                        <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($user_item['ip'] ?? 'N/A') ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400"><?= date('M d, Y', strtotime($user_item['created_at'])) ?></td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex gap-2">
                                <button class="text-blue-400 hover:text-blue-300 transition" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="text-red-400 hover:text-red-300 transition" title="Ban">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
