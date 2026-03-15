<?php
session_start();
require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/functions.php';

try {
    // 1) Ensure users table exists with required columns
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        contact_number VARCHAR(20) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        role ENUM('resident','admin') NOT NULL DEFAULT 'resident',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 1.1) Add columns if they are missing (for existing systems)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL AFTER full_name");
    } catch (Throwable $e) {
        // Ignore if column already exists
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL AFTER contact_number");
    } catch (Throwable $e) {
        // Ignore if column already exists
    }

    // 2) Check if admin user already exists
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $check->execute(['admin']);

    if ($check->fetch()) {
        $message = 'Admin account already exists. You can log in using your admin credentials.';
    } else {
        // 3) Insert admin user with secure hashed password
        $hashed = hashPassword('admin123');
        $insert = $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
        $insert->execute(['admin', $hashed, 'Administrator', 'admin']);
        $message = 'Admin account created successfully. Username: admin | Password: admin123';
    }

    // Optional: Simple output + link to login
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Seed Admin</title>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<style>body{font-family:Arial,Helvetica,sans-serif;max-width:660px;margin:60px auto;padding:0 16px;}'
       . ' .msg{padding:14px 16px;border-radius:8px;background:#f0f7ff;border:1px solid #b6dcff;color:#043c6c;margin-bottom:16px;}'
       . ' a{display:inline-block;margin-top:8px;text-decoration:none;background:#0d6efd;color:#fff;padding:10px 14px;border-radius:6px;}</style>';
    echo '</head><body>';
    echo '<div class="msg">' . htmlspecialchars($message) . '</div>';
    echo '<a href="login.php">Go to Login</a>';
    echo '</body></html>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="white-space:pre-wrap;font-family:monospace;color:#b00020">Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
