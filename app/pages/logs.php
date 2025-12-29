<?php // Logs Page 
$app_secret = $selected_app_data['secret'];
?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">Activity Logs</h2>
    <div class="flex gap-3">
        <select class="px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
            <option>All Events</option>
            <option>Login</option>
            <option>Register</option>
            <option>License Used</option>
        </select>
        <input type="date" class="px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
    </div>
</div>

<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#262641] border-b border-[#2a2a4e]">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Event</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">User</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">IP Address</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Details</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Timestamp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a4e]">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-history text-3xl mb-3 block"></i>
                        No activity logs yet
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
