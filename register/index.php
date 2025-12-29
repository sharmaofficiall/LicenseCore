<?php
require_once '../includes/enhanced.php';

if (is_logged_in()) {
    header("Location: " . SITE_URL . "/app/");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // Validation
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (empty($email)) {
        $error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    }
    
    if (empty($error)) {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists';
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        }
    }
    
    if (empty($error)) {
        $ownerid = generate_owner_id();
        $password_hash = hash_password($password);
        $role = 'owner';
        
        $stmt = $conn->prepare("INSERT INTO accounts (username, email, password, ownerid, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $ownerid, $role);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            header("Location: " . SITE_URL . "/app/");
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#09090d] to-[#1a1a2e]">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2"><?= SITE_NAME ?></h1>
                <p class="text-gray-400">Create your account</p>
            </div>

            <!-- Card -->
            <div class="bg-[#0f0f17] border border-gray-700 rounded-lg p-8 shadow-2xl">
                <!-- Error Alert -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-500/20 border border-red-500 rounded text-red-300">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                </div>
                <?php endif; ?>

                <!-- Register Form -->
                <form method="POST" class="space-y-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            placeholder="Enter username"
                            class="w-full px-4 py-2 bg-[#1a1a2e] border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            required
                        >
                        <p class="text-xs text-gray-400 mt-1">3-70 characters, letters and numbers only</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="your@email.com"
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
                        <p class="text-xs text-gray-400 mt-1">Minimum <?= PASSWORD_MIN_LENGTH ?> characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Confirm Password</label>
                        <input 
                            type="password" 
                            name="confirm" 
                            placeholder="••••••••"
                            class="w-full px-4 py-2 bg-[#1a1a2e] border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <!-- Submit -->
                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200"
                    >
                        Create Account
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-[#0f0f17] text-gray-400">Already have an account?</span>
                    </div>
                </div>

                <!-- Login Link -->
                <div class="mt-6">
                    <a href="<?= SITE_URL ?>/login/" class="w-full block text-center px-4 py-2 border border-gray-600 rounded hover:bg-[#1a1a2e] transition duration-200">
                        Sign In
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-8">
                By registering, you agree to our <a href="#" class="text-blue-500 hover:underline">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>
