<?php // Support Page ?>
<h2 class="text-2xl font-bold mb-8">Support Center</h2>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Contact Form -->
    <div class="lg:col-span-2 bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
        <h3 class="text-lg font-bold mb-6">Submit a Support Ticket</h3>
        
        <form class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Subject</label>
                <input type="text" placeholder="Brief description of your issue" class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Category</label>
                <select class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option>Technical Issue</option>
                    <option>Billing Question</option>
                    <option>Feature Request</option>
                    <option>Account Help</option>
                    <option>Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Priority</label>
                <select class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option>Low</option>
                    <option>Medium</option>
                    <option>High</option>
                    <option>Critical</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea rows="6" placeholder="Provide detailed information about your issue..." class="w-full px-4 py-2 bg-[#262641] border border-[#3a3a5e] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 resize-none"></textarea>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition font-medium">
                <i class="fas fa-paper-plane mr-2"></i> Submit Ticket
            </button>
        </form>
    </div>
    
    <!-- Quick Help -->
    <div class="space-y-6">
        <!-- Contact Info -->
        <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">Contact Us</h3>
            
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-envelope text-blue-400 mt-1"></i>
                    <div>
                        <p class="text-sm font-medium">Email</p>
                        <p class="text-sm text-gray-400 mt-1">support@licenseauth.com</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3">
                    <i class="fab fa-discord text-[#5865F2] mt-1"></i>
                    <div>
                        <p class="text-sm font-medium">Discord</p>
                        <p class="text-sm text-gray-400 mt-1">Join our community</p>
                    </div>
                </div>
                
                <div class="flex items-start gap-3">
                    <i class="fas fa-clock text-green-400 mt-1"></i>
                    <div>
                        <p class="text-sm font-medium">Response Time</p>
                        <p class="text-sm text-gray-400 mt-1">Usually within 24 hours</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="bg-[#1a1a2e] border border-[#2a2a4e] rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4">Common Questions</h3>
            
            <div class="space-y-3">
                <a href="#" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm">
                    <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                    How do I generate a license?
                </a>
                <a href="#" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm">
                    <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                    API integration guide
                </a>
                <a href="#" class="block p-3 bg-[#262641] hover:bg-[#2a2a4e] rounded-lg transition text-sm">
                    <i class="fas fa-question-circle text-blue-400 mr-2"></i>
                    Reset password
                </a>
            </div>
        </div>
    </div>
</div>
