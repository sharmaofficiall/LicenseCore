<?php // Blacklist Page ?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Blacklist Management</h2>
    <button class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition font-medium">
        <i class="fas fa-ban mr-2"></i> Add Ban
    </button>
</div>

<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg">
    <div class="p-6 border-b border-[#2a2a4e]">
        <div class="flex gap-4">
            <button class="px-4 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm font-medium">HWID</button>
            <button class="px-4 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm font-medium">IP Address</button>
            <button class="px-4 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm font-medium">Username</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Type</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Value</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Reason</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Banned</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-ban text-3xl mb-3 block"></i>
                        No blacklisted items
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
