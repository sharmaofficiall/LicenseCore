<?php
include '../includes/config.php';
require_login();

$user = get_current_user_data();

// Handle app creation
$create_error = '';
$create_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_app') {
        $app_name = $_POST['app_name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($app_name)) {
            $create_error = 'Application name is required';
        } else {
            $app_slug = generate_slug($app_name);
            
            // Check if slug exists
            $check = $conn->query("SELECT id FROM applications WHERE app_slug = '$app_slug'");
            if ($check->num_rows > 0) {
                $create_error = 'An application with this name already exists';
            } else {
                $sql = "INSERT INTO applications (user_id, app_name, app_slug, description) VALUES (
                    {$user['id']},
                    '".sanitize($app_name)."',
                    '$app_slug',
                    '".sanitize($description)."'
                )";
                
                if ($conn->query($sql) === TRUE) {
                    $create_success = 'Application created successfully!';
                } else {
                    $create_error = 'Error creating application: ' . $conn->error;
                }
            }
        }
    }
}

// Get user's applications
$apps_result = $conn->query("SELECT * FROM applications WHERE user_id = {$user['id']} ORDER BY created_at DESC");
$applications = [];
while ($app = $apps_result->fetch_assoc()) {
    $licenses = $conn->query("SELECT COUNT(*) as total FROM licenses WHERE app_id = {$app['id']}")->fetch_assoc();
    $app['license_count'] = $licenses['total'];
    $applications[] = $app;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - LicenseAuth</title>
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
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
            font-size: 14px;
        }
        
        .user-info .username {
            font-weight: 600;
            color: #333;
        }
        
        .user-info .plan {
            color: #999;
            font-size: 12px;
        }
        
        .logout-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #5568d3;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .welcome h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome p {
            color: #666;
            font-size: 16px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .section h2 {
            margin-bottom: 20px;
            font-size: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .error {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
        
        .success {
            background: #efe;
            color: #0c0;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0c0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            font-size: 20px;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .app-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .app-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .app-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .app-slug {
            color: #999;
            font-size: 13px;
            font-family: monospace;
            margin-bottom: 12px;
        }
        
        .app-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .app-stats {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 12px;
        }
        
        .app-stat {
            text-align: center;
            font-size: 12px;
        }
        
        .app-stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
        
        .app-stat-label {
            color: #999;
        }
        
        .app-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .app-actions a, .app-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
            background: #667eea;
            color: white;
            transition: background 0.3s;
        }
        
        .app-actions a:hover, .app-actions button:hover {
            background: #5568d3;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🔐 LicenseCore</div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="username"><?= sanitize($user['username']) ?></div>
                    <div class="plan">Plan: <?= ucfirst(sanitize($user['plan'])) ?></div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h1>Welcome, <?= sanitize($user['username']) ?>! 👋</h1>
            <p>Manage your applications and licenses from your dashboard</p>
        </div>
        
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">Your Applications</h2>
                <button class="btn" onclick="openModal('createAppModal')">+ Create App</button>
            </div>
            
            <?php if ($create_error): ?>
                <div class="error">✗ <?= sanitize($create_error) ?></div>
            <?php endif; ?>
            
            <?php if ($create_success): ?>
                <div class="success">✓ <?= sanitize($create_success) ?></div>
            <?php endif; ?>
            
            <?php if (count($applications) > 0): ?>
                <div class="apps-grid">
                    <?php foreach ($applications as $app): ?>
                    <div class="app-card">
                        <div class="app-name"><?= sanitize($app['app_name']) ?></div>
                        <div class="app-slug">@<?= sanitize($app['app_slug']) ?></div>
                        <div class="app-description"><?= sanitize($app['description']) ?: 'No description' ?></div>
                        <div class="app-stats">
                            <div class="app-stat">
                                <div class="app-stat-number"><?= $app['license_count'] ?></div>
                                <div class="app-stat-label">Licenses</div>
                            </div>
                            <div class="app-stat">
                                <div class="app-stat-number"><?= ucfirst($app['status']) ?></div>
                                <div class="app-stat-label">Status</div>
                            </div>
                        </div>
                        <div class="app-actions">
                            <a href="app.php?id=<?= $app['id'] ?>">Manage</a>
                            <button onclick="deleteApp(<?= $app['id'] ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No applications yet</h3>
                    <p>Create your first application to get started with license management</p>
                    <button class="btn" onclick="openModal('createAppModal')" style="margin-top: 20px;">Create Your First App</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create App Modal -->
    <div id="createAppModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Application</h3>
                <span class="close" onclick="closeModal('createAppModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_app">
                <div class="form-group">
                    <label for="app_name">Application Name*</label>
                    <input 
                        type="text" 
                        id="app_name" 
                        name="app_name" 
                        placeholder="My Awesome App"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Describe your application"
                        rows="4"
                    ></textarea>
                </div>
                <button type="submit" class="btn">Create Application</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function deleteApp(appId) {
            if (confirm('Are you sure you want to delete this application? This cannot be undone.')) {
                window.location.href = 'delete_app.php?id=' + appId;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = event.target;
            if (modal.classList.contains('modal')) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>