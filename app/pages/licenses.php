<?php
// License Keys Management Page
$app_secret = $selected_app_data['secret'];

// Get all license keys for this app  
$stmt = $conn->prepare("SELECT * FROM `keys` WHERE app = ? ORDER BY created_at DESC LIMIT 1000");
$stmt->bind_param("s", $app_secret);
$stmt->execute();
$keys_result = $stmt->get_result();
$keys = [];

while ($key = $keys_result->fetch_assoc()) {
    $keys[] = $key;
}

// Get statistics
$total_keys = count($keys);
$active_keys = count(array_filter($keys, fn($k) => $k['status'] === 'active'));
$used_keys = count(array_filter($keys, fn($k) => !empty($k['used_by'])));
?>

<!-- Success/Error Messages -->
<?php if (!empty($license_success)): ?>
<div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg flex items-center gap-3 animate-pulse">
    <i class="fas fa-check-circle text-green-400 text-xl"></i>
    <div class="flex-1">
        <p class="text-green-300 font-semibold"><?= htmlspecialchars($license_success) ?></p>
        <?php if (!empty($generated_keys)): ?>
        <div class="mt-3 p-3 bg-[#0f0f17] rounded border border-green-500/30 max-h-48 overflow-y-auto">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs text-gray-400">Generated Keys:</p>
                <button onclick="copyAllKeys()" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1">
                    <i class="fas fa-copy"></i> Copy All
                </button>
            </div>
            <?php foreach ($generated_keys as $gkey): ?>
            <div class="flex items-center gap-2 mb-1.5 group">
                <code class="text-sm text-green-400 font-mono flex-1"><?= htmlspecialchars($gkey) ?></code>
                <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($gkey) ?>')" class="opacity-0 group-hover:opacity-100 text-xs text-blue-400 hover:text-blue-300 transition">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($license_error)): ?>
<div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
    <p class="text-red-300 font-semibold"><?= htmlspecialchars($license_error) ?></p>
</div>
<?php endif; ?>

<!-- Header with Stats -->
<div class="flex justify-between items-start mb-8">
    <div>
        <h2 class="text-3xl font-bold mb-2 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">License Keys</h2>
        <div class="flex gap-4 text-sm">
            <span class="text-gray-400">Total: <span class="text-white font-semibold"><?= $total_keys ?></span></span>
            <span class="text-gray-400">Active: <span class="text-green-400 font-semibold"><?= $active_keys ?></span></span>
            <span class="text-gray-400">Used: <span class="text-blue-400 font-semibold"><?= $used_keys ?></span></span>
        </div>
    </div>
    <button onclick="document.getElementById('generateModal').classList.remove('hidden')" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg transition font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:scale-105">
        <i class="fas fa-plus"></i> Generate Keys
    </button>
</div>

<!-- Generate Key Modal -->
<div id="generateModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-gradient-to-b from-[#1a1a2e] to-[#15152a] border border-[#2a2a4e] rounded-2xl p-8 max-w-2xl w-full shadow-2xl transform transition-all" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">Generate License Keys</h3>
            <button onclick="document.getElementById('generateModal').classList.add('hidden')" class="text-gray-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="generate_key">
            <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300 flex items-center gap-2">
                        <i class="fas fa-hashtag text-blue-400"></i> Amount
                    </label>
                    <input 
                        type="number" 
                        name="amount" 
                        value="1"
                        min="1"
                        max="100"
                        class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition"
                        placeholder="Number of keys"
                    >
                    <p class="text-xs text-gray-500 mt-1">Max 100 keys at once</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300 flex items-center gap-2">
                        <i class="fas fa-clock text-yellow-400"></i> Duration
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input 
                            type="number" 
                            name="duration" 
                            value="30"
                            min="1"
                            class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition"
                            placeholder="Value"
                        >
                        <select 
                            name="duration_unit"
                            class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition"
                        >
                            <option value="days" selected>Days</option>
                            <option value="hours">Hours</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Choose days or hours for expiry</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300 flex items-center gap-2">
                        <i class="fas fa-layer-group text-purple-400"></i> Level
                    </label>
                    <select 
                        name="level"
                        class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition"
                    >
                        <option value="1">Level 1 - Basic</option>
                        <option value="2">Level 2 - Pro</option>
                        <option value="3">Level 3 - Premium</option>
                        <option value="4">Level 4 - Enterprise</option>
                        <option value="5">Level 5 - Ultimate</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Access level for users</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-300 flex items-center gap-2">
                        <i class="fas fa-code text-green-400"></i> Format
                    </label>
                    <select 
                        name="format"
                        class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition"
                    >
                        <option value="default">Default (UUID)</option>
                        <option value="alphanum">Alphanumeric</option>
                        <option value="random">Random String</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Key format type</p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-gray-300 flex items-center gap-2">
                    <i class="fas fa-sticky-note text-pink-400"></i> Note (Optional)
                </label>
                <textarea 
                    name="note"
                    rows="3"
                    class="w-full px-4 py-3 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/50 transition resize-none"
                    placeholder="Add a note for these keys (e.g., 'For promotional campaign')"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">Internal note for key identification</p>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-[#2a2a4e]">
                <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-lg transition font-semibold shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-key mr-2"></i> Generate Keys
                </button>
                <button type="button" onclick="document.getElementById('generateModal').classList.add('hidden')" class="px-6 py-3 bg-[#262641] hover:bg-[#2a2a4e] border border-[#3a3a5e] rounded-lg transition font-semibold">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- License Keys Table -->
<div class="bg-gradient-to-b from-[#1a1a2e] to-[#15152a] border border-[#2a2a4e] rounded-xl overflow-hidden shadow-xl">
    <!-- Table Header with Search and Filters -->
    <div class="p-4 bg-gradient-to-r from-[#1f1f3a] to-[#1a1a2e] border-b border-[#2a2a4e] flex justify-between items-center">
        <div class="flex gap-3">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <input 
                    type="text" 
                    id="searchKeys" 
                    placeholder="Search keys..." 
                    class="pl-10 pr-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white text-sm focus:outline-none focus:border-blue-500 w-64" 
                    onkeyup="filterKeys()"
                >
            </div>
            <select id="filterStatus" class="px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white text-sm focus:outline-none focus:border-blue-500" onchange="filterKeys()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="used">Used</option>
                <option value="expired">Expired</option>
            </select>
        </div>
        <span class="text-sm text-gray-400">Showing <span class="text-white font-semibold" id="keyCount"><?= count($keys) ?></span> keys</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full" id="keysTable">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">License Key</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Format</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Status</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Used By</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Expires</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Created By</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Created</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-300">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <?php if (empty($keys)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                        <div class="flex flex-col items-center">
                            <div class="w-20 h-20 rounded-full bg-blue-500/10 flex items-center justify-center mb-4">
                                <i class="fas fa-key text-blue-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-semibold mb-2">No license keys yet</p>
                            <p class="text-sm text-gray-500 mb-4">Generate your first license key to get started</p>
                            <button onclick="document.getElementById('generateModal').classList.remove('hidden')" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                                <i class="fas fa-plus mr-2"></i> Generate Keys
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($keys as $key): 
                        $expiry_ts = intval($key['expiry'] ?? 0);
                        $is_expired = $expiry_ts > 0 ? ($expiry_ts < time()) : false;
                        $is_used = !empty($key['used_by']);
                    ?>
                    <tr class="hover:bg-[#262641] transition key-row" data-status="<?= $is_expired ? 'expired' : ($is_used ? 'used' : strtolower($key['status'] ?? 'active')) ?>" data-key="<?= htmlspecialchars($key['license_key']) ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <code class="font-mono text-sm text-blue-400 font-semibold"><?= htmlspecialchars(substr($key['license_key'], 0, 24)) ?>...</code>
                                <button onclick="copyKey('<?= htmlspecialchars($key['license_key']) ?>')" class="text-gray-400 hover:text-blue-400 transition" title="Copy">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-purple-500/20 text-purple-400 text-xs rounded-full font-semibold"><?= strtoupper($key['format'] ?? 'UUID') ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($is_expired): ?>
                                <span class="px-3 py-1 bg-red-500/20 text-red-400 text-xs rounded-full font-semibold flex items-center gap-1.5 w-fit">
                                    <i class="fas fa-times-circle"></i> Expired
                                </span>
                            <?php elseif ($is_used): ?>
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-full font-semibold flex items-center gap-1.5 w-fit">
                                    <i class="fas fa-check-circle"></i> Used
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 text-xs rounded-full font-semibold flex items-center gap-1.5 w-fit">
                                    <i class="fas fa-circle"></i> Active
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($is_used): ?>
                                <span class="text-gray-300 font-medium"><?= htmlspecialchars($key['used_by']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-500 italic">Unused</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($expiry_ts > 0): ?>
                            <div class="<?= $is_expired ? 'text-red-400' : 'text-gray-300' ?>">
                                <div class="font-medium"><?= date('M d, Y', $expiry_ts) ?></div>
                                <div class="text-xs text-gray-500"><?= date('H:i', $expiry_ts) ?></div>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-500">No expiry</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400 max-w-xs truncate" title="<?= htmlspecialchars($key['created_by'] ?? '-') ?>">
                            <?= htmlspecialchars($key['created_by'] ?? '-') ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div><?= date('M d, Y', strtotime($key['created_at'])) ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex gap-2">
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this license key?')">
                                    <input type="hidden" name="action" value="delete_key">
                                    <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                    <input type="hidden" name="app" value="<?= htmlspecialchars($app_secret) ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 transition p-1.5 hover:bg-red-500/10 rounded" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <script>
    function copyKey(key) {
        navigator.clipboard.writeText(key).then(() => {
            showToast('Key copied to clipboard!', 'success');
        });
    }

    function copyAllKeys() {
        const keys = <?= json_encode($generated_keys ?? []) ?>;
        const keysText = keys.join('\n');
        navigator.clipboard.writeText(keysText).then(() => {
            showToast('All keys copied to clipboard!', 'success');
        });
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'} mr-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }

    function filterKeys() {
        const search = document.getElementById('searchKeys').value.toLowerCase();
        const status = document.getElementById('filterStatus').value.toLowerCase();
        const rows = document.querySelectorAll('.key-row');
        let count = 0;
    
        rows.forEach(row => {
            const key = row.dataset.key.toLowerCase();
            const rowStatus = row.dataset.status.toLowerCase();
            const matchSearch = key.includes(search);
            const matchStatus = !status || rowStatus === status;
        
            if (matchSearch && matchStatus) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
    
        document.getElementById('keyCount').textContent = count;
    }
    </script>
    </div>
</div>
