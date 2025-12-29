<?php // Documentation Page ?>
<h2 class="text-2xl font-bold mb-8">API Documentation</h2>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Navigation -->
    <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="font-bold mb-4">Quick Links</h3>
        <div class="space-y-2">
            <a href="#getting-started" class="block px-3 py-2 bg-[#262641] hover:bg-[#2a2a4e] rounded transition text-sm">Getting Started</a>
            <a href="#authentication" class="block px-3 py-2 hover:bg-[#262641] rounded transition text-sm">Authentication</a>
            <a href="#endpoints" class="block px-3 py-2 hover:bg-[#262641] rounded transition text-sm">API Endpoints</a>
            <a href="#examples" class="block px-3 py-2 hover:bg-[#262641] rounded transition text-sm">Code Examples</a>
        </div>
    </div>
    
    <!-- Content -->
    <div class="lg:col-span-3 space-y-6">
        <!-- Getting Started -->
        <div id="getting-started" class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">Getting Started</h3>
            <p class="text-gray-400 mb-4">Welcome to the LicenseAuth API documentation. This guide will help you integrate our authentication system into your applications.</p>
            
            <div class="bg-[#262641] rounded-lg p-4">
                <p class="text-xs text-gray-400 mb-2">Base URL</p>
                <code class="text-sm text-blue-400"><?= SITE_URL ?>/api/1.2/</code>
            </div>
        </div>
        
        <!-- Authentication -->
        <div id="authentication" class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">Authentication</h3>
            <p class="text-gray-400 mb-4">All API requests require authentication using your application secret.</p>
            
            <div class="bg-[#0f0f17] border border-[#2a2a4e] rounded-lg p-4 font-mono text-sm overflow-x-auto">
                <pre class="text-gray-300">POST <?= SITE_URL ?>/api/1.2/index.php
Content-Type: application/x-www-form-urlencoded

name=YourAppName
ownerid=<?= htmlspecialchars($ownerid) ?>
secret=YOUR_APP_SECRET
type=init</pre>
            </div>
        </div>
        
        <!-- Endpoints -->
        <div id="endpoints" class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">API Endpoints</h3>
            
            <div class="space-y-4">
                <div class="border-l-4 border-blue-500 pl-4">
                    <h4 class="font-semibold">Initialize (init)</h4>
                    <p class="text-sm text-gray-400 mt-1">Initialize a session and verify app credentials</p>
                    <code class="text-xs text-blue-400 mt-2 block">type=init&ver=1.0&enckey=yourkey</code>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h4 class="font-semibold">User Login</h4>
                    <p class="text-sm text-gray-400 mt-1">Authenticate a user with username and password</p>
                    <code class="text-xs text-green-400 mt-2 block">type=login&sessionid=xxx&username=user&pass=password&hwid=xxx</code>
                </div>
                
                <div class="border-l-4 border-purple-500 pl-4">
                    <h4 class="font-semibold">License Validation</h4>
                    <p class="text-sm text-gray-400 mt-1">Validate a license key</p>
                    <code class="text-xs text-purple-400 mt-2 block">type=license&sessionid=xxx&key=LICENSE_KEY&hwid=xxx</code>
                </div>
                
                <div class="border-l-4 border-yellow-500 pl-4">
                    <h4 class="font-semibold">Register User</h4>
                    <p class="text-sm text-gray-400 mt-1">Register a new user with license key</p>
                    <code class="text-xs text-yellow-400 mt-2 block">type=register&sessionid=xxx&username=user&pass=password&key=LICENSE_KEY</code>
                </div>
                
                <div class="border-l-4 border-red-500 pl-4">
                    <h4 class="font-semibold">Check Session</h4>
                    <p class="text-sm text-gray-400 mt-1">Verify session is still valid</p>
                    <code class="text-xs text-red-400 mt-2 block">type=check&sessionid=xxx</code>
                </div>
            </div>
        </div>
        
        <!-- Examples -->
        <div id="examples" class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">PHP Example</h3>
            
            <div class="bg-[#0f0f17] border border-[#2a2a4e] rounded-lg p-4 font-mono text-sm overflow-x-auto">
                <pre class="text-gray-300"><span class="text-purple-400">&lt;?php</span>
<span class="text-blue-400">$api_url</span> = <span class="text-green-400">'<?= SITE_URL ?>/api/1.2/'</span>;
<span class="text-blue-400">$data</span> = <span class="text-yellow-400">array</span>(
    <span class="text-green-400">'type'</span> => <span class="text-green-400">'init'</span>,
    <span class="text-green-400">'ver'</span> => <span class="text-green-400">'1.0'</span>,
    <span class="text-green-400">'name'</span> => <span class="text-green-400">'YourAppName'</span>,
    <span class="text-green-400">'ownerid'</span> => <span class="text-green-400">'<?= substr($ownerid, 0, 16) ?>...'</span>,
    <span class="text-green-400">'secret'</span> => <span class="text-green-400">'YOUR_APP_SECRET'</span>
);

<span class="text-blue-400">$ch</span> = <span class="text-yellow-400">curl_init</span>();
<span class="text-yellow-400">curl_setopt</span>(<span class="text-blue-400">$ch</span>, <span class="text-orange-400">CURLOPT_URL</span>, <span class="text-blue-400">$api_url</span>);
<span class="text-yellow-400">curl_setopt</span>(<span class="text-blue-400">$ch</span>, <span class="text-orange-400">CURLOPT_POST</span>, <span class="text-orange-400">true</span>);
<span class="text-yellow-400">curl_setopt</span>(<span class="text-blue-400">$ch</span>, <span class="text-orange-400">CURLOPT_POSTFIELDS</span>, <span class="text-yellow-400">http_build_query</span>(<span class="text-blue-400">$data</span>));
<span class="text-yellow-400">curl_setopt</span>(<span class="text-blue-400">$ch</span>, <span class="text-orange-400">CURLOPT_RETURNTRANSFER</span>, <span class="text-orange-400">true</span>);

<span class="text-blue-400">$response</span> = <span class="text-yellow-400">curl_exec</span>(<span class="text-blue-400">$ch</span>);
<span class="text-blue-400">$result</span> = <span class="text-yellow-400">json_decode</span>(<span class="text-blue-400">$response</span>, <span class="text-orange-400">true</span>);

<span class="text-yellow-400">print_r</span>(<span class="text-blue-400">$result</span>);
<span class="text-purple-400">?></span></pre>
            </div>
        </div>
    </div>
</div>
