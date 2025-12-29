<?php // Subscriptions Page ?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Subscriptions</h2>
    <button onclick="document.getElementById('addSubModal').classList.remove('hidden')" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
        <i class="fas fa-plus mr-2"></i> Create Subscription
    </button>
</div>

<!-- Subscriptions Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 text-center">
        <i class="fas fa-inbox text-3xl text-gray-600 mb-4"></i>
        <p class="text-gray-400">No subscriptions created yet</p>
    </div>
</div>
