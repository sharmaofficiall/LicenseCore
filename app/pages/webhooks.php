<?php // Webhooks Page ?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Webhooks</h2>
    <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
        <i class="fas fa-plus mr-2"></i> Add Webhook
    </button>
</div>

<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">URL</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Event</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-webhook text-3xl mb-3 block"></i>
                        No webhooks configured
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
