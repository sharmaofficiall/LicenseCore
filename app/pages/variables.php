<?php // Variables Page ?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Variables</h2>
    <button onclick="document.getElementById('addVarModal').classList.remove('hidden')" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
        <i class="fas fa-plus mr-2"></i> Add Variable
    </button>
</div>

<!-- Add Variable Modal -->
<div id="addVarModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-8 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold mb-6">Add Variable</h3>
        <form class="space-y-4">
            <input type="text" placeholder="Variable Name" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
            <input type="text" placeholder="Variable Value" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">Add</button>
                <button type="button" onclick="document.getElementById('addVarModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition font-medium">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Variables Table -->
<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg overflow-hidden">
    <div class="p-6 border-b border-[#2a2a4e]">
        <p class="text-gray-400">Global variables that can be accessed by users</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Variable Name</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Value</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Created</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <tr class="hover:bg-[#262641] transition">
                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-inbox text-3xl mb-3 block"></i>
                        No variables yet
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
