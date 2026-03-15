<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// DEFINE VARIABLES AT THE TOP
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitizeInput($_POST['full_name']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $address = sanitizeInput($_POST['address']);
    $role = 'resident';

    if (empty($username) || empty($password) || empty($full_name) || empty($contact_number) || empty($address)) {
        $error = "All fields are required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username already exists";
        } else {
            $hashed_password = hashPassword($password);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, contact_number, address, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $contact_number, $address, $role]);
            
            header("Location: login.php?msg=registered");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barangay Digital Clearance System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-container">
        <div class="login-box">
            <div class="box-header">
                <h1>Create Account</h1>
                <p>Join our Barangay Digital Clearance System</p>
            </div>

            <div class="box-content">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                    <input type="text" name="contact_number" placeholder="Contact Number (e.g. 09123456789)" required>
                    <input type="text" name="address" placeholder="Complete Address (House No., St., Brgy)" required>
                    <input type="text" name="username" placeholder="Choose a username" required>
                    <input type="password" name="password" placeholder="Minimum 6 characters" required>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                    <button type="submit" class="btn btn-register">Create Account</button>
                </form>

                <div class="action-buttons">
                    <a href="login.php" class="btn btn-login">Have an Account? Login</a>
                    <a href="index.php" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>