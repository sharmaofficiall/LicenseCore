<?php
require_once '../includes/enhanced.php';

if (is_logged_in()) {
    header("Location: " . SITE_URL . "/app/");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } else {
        // Check rate limiting
        $identifier = 'login:' . get_ip();
        if (!check_rate_limit($identifier, 5, 900)) {
            $error = 'Too many login attempts. Please try again later.';
        } else {
            // Get user
            $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (!$user) {
                $error = 'Username or password incorrect';
            } elseif ($user['banned']) {
                $error = 'Your account has been banned';
            } elseif (!verify_password($password, $user['password'])) {
                $error = 'Username or password incorrect';
            } else {
                // Update last login
                $stmt = $conn->prepare("UPDATE accounts SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Set session
                $_SESSION['username'] = $username;
                $_SESSION['ownerid'] = $user['ownerid'];
                $_SESSION['user_id'] = $user['id'];
                
                // Log action
                log_action($user['ownerid'], 'login', $username, 'User logged in');
                
                header("Location: " . SITE_URL . "/app/");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#09090d] to-[#1a1a2e]">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2"><?= SITE_NAME ?></h1>
                <p class="text-gray-400">Sign in to your account</p>
            </div>

            <!-- Card -->
            <div class="bg-[#0f0f17] border border-gray-700 rounded-lg p-8 shadow-2xl">
                <!-- Error Alert -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-500/20 border border-red-500 rounded text-red-300">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="space-y-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            placeholder="Enter your username"
                            class="w-full px-4 py-2 bg-[#1a1a2e] border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            placeholder="••••••••"
                            class="w-full px-4 py-2 bg-[#1a1a2e] border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded bg-[#1a1a2e] border-gray-600" name="remember">
                            <span class="ml-2 text-sm">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-blue-500 hover:underline">Forgot password?</a>
                    </div>

                    <!-- Submit -->
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200"
                    >
                        Sign In
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-[#0f0f17] text-gray-400">Don't have an account?</span>
                    </div>
                </div>

                <!-- Register Link -->
                <div class="mt-6">
                    <a href="<?= SITE_URL ?>/register/" class="w-full block text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded transition duration-200 font-medium">
                        Create Account
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-8">
                Protected by © 2024 <?= SITE_NAME ?>. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
