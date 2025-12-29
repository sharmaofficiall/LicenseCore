<?php // API Keys Page ?>
<div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-bold">API Keys</h2>
    <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
        <i class="fas fa-plus mr-2"></i> Generate API Key
    </button>
</div>

<div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6 mb-8">
    <h3 class="text-lg font-bold mb-4">Your API Keys</h3>
    <p class="text-gray-400 text-sm mb-6">API keys allow you to integrate LicenseAuth into your applications.</p>
    
    <div class="space-y-3">
        <div class="bg-[#262641] rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <i class="fas fa-key text-blue-400 text-xl"></i>
                <div>
                    <p class="font-semibold">Production API Key</p>
                    <p class="font-mono text-sm text-gray-400 mt-1">sk_live_************</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded transition text-sm">
                    <i class="fas fa-eye mr-1"></i> Reveal
                </button>
                <button class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded transition text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- API Documentation Link -->
<div class="bg-[#1a1a2e] border border-blue-500/50 rounded-lg p-6">
    <h3 class="text-lg font-bold mb-2">API Documentation</h3>
    <p class="text-gray-400 text-sm mb-4">Learn how to integrate our API into your applications</p>
    <a href="?page=documentation" class="inline-block px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
        <i class="fas fa-book mr-2"></i> View Documentation
    </a>
</div>
