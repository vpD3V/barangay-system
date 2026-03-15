<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// Security Check
if ($_SESSION['role'] !== 'resident') {
    die("Access Denied - Only Residents Can Create Requests");
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitizeInput($_POST['request_type']);
    $code = generateVerificationCode();

    $stmt = $pdo->prepare("INSERT INTO requests (user_id, request_type, verification_code) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $type, $code]);
    
    $success = "Request submitted successfully! Verification Code: " . $code;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Request - Barangay System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h2>📝 New Request</h2>
            <p>Create a new clearance request</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="form-card">
            <div class="form-group">
                <label>Request Type:</label>
                <select name="request_type" required>
                    <option value="clearance">Barangay Clearance</option>
                    <option value="indigency">Indigency Certificate</option>
                </select>
            </div>
            <button type="submit" class="btn btn-register">Submit Request</button>
        </form>
        
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>