<?php
include 'includes/config.php';
include 'includes/auth.php';

$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: " . SITE_URL . "/admin/dashboard.php");
    exit();
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    $result = register_user($username, $email, $password, $password_confirm);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Account - LicenseAuth</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-hint {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .error {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
            font-size: 14px;
        }
        
        .success {
            background: #efe;
            color: #0c0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0c0;
            font-size: 14px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .terms {
            font-size: 12px;
            color: #999;
            margin-top: 15px;
            line-height: 1.5;
        }
        
        .terms a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>🔐 LicenseAuth</h1>
                <p>License Management System</p>
            </div>
            
            <h2>Create Account</h2>
            <p class="subtitle">Get your free LicenseAuth account now</p>
            
            <?php if ($error): ?>
                <div class="error">✗ <?= sanitize($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">✓ <?= sanitize($success) ?></div>
                <div style="text-align: center; margin-top: 20px;">
                    <p>Redirecting to login...</p>
                    <script>setTimeout(() => window.location.href = 'login.php', 2000);</script>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username*</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Choose a username"
                        value="<?= sanitize($_POST['username'] ?? '') ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your@email.com"
                        value="<?= sanitize($_POST['email'] ?? '') ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password*</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Min 12 characters"
                        required
                    >
                    <div class="password-hint">Use 12 or more characters with a mix of letters, numbers & symbols</div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirm Password*</label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        placeholder="Confirm password"
                        required
                    >
                </div>
                
                <div class="terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms" style="display: inline; margin: 0;">
                        I agree to the Terms of Service
                    </label>
                </div>
                
                <button type="submit" style="margin-top: 20px;">Sign Up</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
                © 2021 - 2025 LicenseAuth. Made with ❤ by DeadShot
            </div>
        </div>
    </div>
</body>
</html>
