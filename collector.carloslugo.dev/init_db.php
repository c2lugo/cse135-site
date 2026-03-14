<?php
$host = 'localhost';
$db   = 'cse135_analytics';
$user = 'analytics_user';
$pass = 'cse135pw';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create users table for Role-Based Access
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('superadmin', 'analyst', 'viewer') NOT NULL,
        permissions VARCHAR(255) DEFAULT 'all'
    )");

    // 2. Create reports table for Analyst Comments
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        author_id INT NOT NULL,
        category VARCHAR(50) NOT NULL,
        analyst_comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 3. Insert/update the required grader test accounts
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, permissions) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), permissions = VALUES(permissions)");
    
    // Passwords are all set to 'password123' for easy testing
    $hash = password_hash('password123', PASSWORD_DEFAULT);
    
    $users = [
        ['superadmin', $hash, 'superadmin', 'all'],
        ['elijah', $hash, 'analyst', 'performance,static,activity_batch'],
        ['damian', $hash, 'analyst', 'performance,static'],
        ['sam', $hash, 'analyst', 'performance'],
        ['sally', $hash, 'analyst', 'activity_batch,static'],
        ['viewer', $hash, 'viewer', 'saved_reports']
    ];

    foreach ($users as $u) {
        $stmt->execute($u);
    }

    echo "SUCCESS: Database upgraded with roles and secure test users!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
