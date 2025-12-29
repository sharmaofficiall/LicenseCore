<?php
include 'includes/config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: " . SITE_URL . "/admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LicenseAuth - License Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        .nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav a:hover {
            color: #667eea;
        }
        
        .auth-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 20px;
            color: #666;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 20px;
        }
        
        .feature {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .feature:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }
        
        .feature h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #333;
        }
        
        .feature p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-top: 80px;
        }
        
        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .footer {
            background: #333;
            color: #999;
            text-align: center;
            padding: 30px 20px;
            margin-top: 50px;
        }
        
        .cta-container {
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🔐 LicenseAuth</div>
            <nav class="nav">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary">Sign In</a>
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                </div>
            </nav>
        </div>
    </div>
    
    <div class="hero">
        <h1>The Better Way to Manage Your Application</h1>
        <p>Simple, secure license management for your software. Generate, validate, and track licenses with ease.</p>
        <a href="register.php" class="btn btn-primary">Get Started Free →</a>
    </div>
    
    <div class="features" id="features">
        <div class="feature">
            <h3>🔑 License Generation</h3>
            <p>Generate unique, secure license keys for your applications instantly with customizable options.</p>
        </div>
        <div class="feature">
            <h3>⚡ Real-time Validation</h3>
            <p>Validate licenses in real-time with our powerful API. Check status, activations, and expiry dates.</p>
        </div>
        <div class="feature">
            <h3>📊 Analytics Dashboard</h3>
            <p>Track license activations, usage patterns, and customer insights from an intuitive dashboard.</p>
        </div>
        <div class="feature">
            <h3>🌍 Multiple Applications</h3>
            <p>Manage licenses for multiple applications from a single account. Organize and track everything.</p>
        </div>
        <div class="feature">
            <h3>🛡️ Advanced Security</h3>
            <p>Encrypted keys, secure API endpoints, and role-based access control for maximum security.</p>
        </div>
        <div class="feature">
            <h3>🔄 Easy Integration</h3>
            <p>Simple REST API for integration. Just a few lines of code to add license validation to your app.</p>
        </div>
    </div>
    
    <div class="cta">
        <div class="cta-container">
            <h2>Ready to Protect Your Software?</h2>
            <p>Join thousands of developers using LicenseAuth</p>
            <a href="register.php" class="btn btn-secondary" style="background: white; color: #667eea; border: none;">Create Free Account →</a>
        </div>
    </div>
    
    <div class="footer">
        <p>© 2021 - 2025 LicenseAuth. Made with ❤ by DeadShot</p>
    </div>
</body>
</html>