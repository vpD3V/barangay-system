<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Calculate stats
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM requests");
    $totalRequests = $stmt->fetch()['total'];
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM requests WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalRequests = $stmt->fetch()['total'];
}

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM requests WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barangay System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h3>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h3>
            <p>Manage your clearance requests</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <h4>Total Requests</h4>
                <h2><?= $totalRequests ?></h2>
                <p>All time</p>
            </div>
            
            <?php if($role === 'resident'): ?>
            <div class="stat-card success">
                <h4>Quick Action: Create a New Request</h4>
                <p>Start a Barangay Clearance or Indigency request.</p>
                <a href="create_request.php" class="btn white-btn">+ New Request</a>
            </div>
            <?php else: ?>
            <div class="stat-card warning">
                <h4>Admin Quick Action</h4>
                <p>Review and process pending submissions.</p>
                <a href="approve_request.php" class="btn white-btn">Approve Requests</a>
            </div>
            <?php endif; ?>
        </div>
        
            </div>
</body>
</html>