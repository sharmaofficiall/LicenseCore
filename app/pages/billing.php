<?php // Billing Page ?>
<h2 class="text-2xl font-bold mb-8">Billing & Subscription</h2>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Current Plan -->
    <div class="lg:col-span-2 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Current Plan</h3>
        
        <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 border border-blue-500/50 rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h4 class="text-2xl font-bold">Free Plan</h4>
                    <p class="text-gray-400 mt-1">Perfect for getting started</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold">$0</p>
                    <p class="text-sm text-gray-400">per month</p>
                </div>
            </div>
        </div>
        
        <h4 class="font-semibold mb-4">Plan Features</h4>
        <ul class="space-y-2">
            <li class="flex items-center gap-2">
                <i class="fas fa-check text-green-400"></i>
                <span>Up to 3 applications</span>
            </li>
            <li class="flex items-center gap-2">
                <i class="fas fa-check text-green-400"></i>
                <span>100 license keys per app</span>
            </li>
            <li class="flex items-center gap-2">
                <i class="fas fa-check text-green-400"></i>
                <span>Basic support</span>
            </li>
        </ul>
    </div>
    
    <!-- Upgrade -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-4">Upgrade</h3>
        <p class="text-gray-400 text-sm mb-6">Unlock more features with our Pro plan</p>
        <button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
            <i class="fas fa-star mr-2"></i> Upgrade to Pro
        </button>
    </div>
</div>
