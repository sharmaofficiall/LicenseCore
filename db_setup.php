<?php
/**
 * LicenseAuth - Database Setup Script
 * Creates all necessary tables for license management system
 */

// First, connect without selecting database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "licenseauth";

$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . $dbname;
if ($conn->query($sql) === TRUE) {
    echo "✓ Database created successfully<br>";
} else {
    echo "✗ Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db($dbname);

// Create Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plan VARCHAR(50) DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Users table created successfully<br>";
} else {
    echo "✗ Error creating users table: " . $conn->error . "<br>";
}

// Create Applications table
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    app_name VARCHAR(255) NOT NULL,
    app_slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_app_slug (app_slug)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Applications table created successfully<br>";
} else {
    echo "✗ Error creating applications table: " . $conn->error . "<br>";
}

// Create Licenses table
$sql = "CREATE TABLE IF NOT EXISTS licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    license_key VARCHAR(255) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active',
    activation_date TIMESTAMP,
    expiry_date DATETIME,
    max_activations INT DEFAULT 1,
    current_activations INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_app_id (app_id),
    INDEX idx_license_key (license_key),
    INDEX idx_status (status)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Licenses table created successfully<br>";
} else {
    echo "✗ Error creating licenses table: " . $conn->error . "<br>";
}

// Create Activations table
$sql = "CREATE TABLE IF NOT EXISTS activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    hwid VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    device_name VARCHAR(255),
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_verified DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    INDEX idx_license_id (license_id),
    INDEX idx_hwid (hwid)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Activations table created successfully<br>";
} else {
    echo "✗ Error creating activations table: " . $conn->error . "<br>";
}

// Create Sessions table
$sql = "CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token)
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Sessions table created successfully<br>";
} else {
    echo "✗ Error creating sessions table: " . $conn->error . "<br>";
}

echo "<br><hr>";
echo "<h2>✓ Database setup completed!</h2>";
echo "<p><a href='index.php'>← Go to Home</a></p>";

$conn->close();
?>