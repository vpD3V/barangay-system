<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get notification counts by status
if ($role === 'admin') {
    // Admin sees counts for ALL requests in the system
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM requests GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Admin sees ALL notifications (joined with user info to see who requested)
    $stmt = $pdo->query("SELECT r.*, u.full_name FROM requests r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    $notifications = $stmt->fetchAll();
} else {
    // Resident sees only their own
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM requests WHERE user_id = ? GROUP BY status");
    $stmt->execute([$userId]);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get resident notifications
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
}

$pendingCount = $statusCounts['pending'] ?? 0;
$approvedCount = $statusCounts['approved'] ?? 0;
$rejectedCount = $statusCounts['rejected'] ?? 0;
$totalCount = $pendingCount + $approvedCount + $rejectedCount;

// Filter by status
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all' && $filter !== 'pending' && $filter !== 'approved' && $filter !== 'rejected') {
    $filter = 'all';
}

// Filter notifications
if ($filter === 'all') {
    $displayNotifications = $notifications;
} else {
    $displayNotifications = array_filter($notifications, function($notif) use ($filter) {
        return $notif['status'] === $filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Barangay System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h2>🔔 Notification Center</h2>
            <p>Stay updated with your request status</p>
        </div>
        
        <!-- Summary Cards as Filters -->
        <div class="stats-grid">
            <a href="notifications.php?filter=pending" class="stat-link <?= $filter==='pending' ? 'active' : '' ?>">
                <div class="stat-card warning">
                    <h4>⏳ Pending</h4>
                    <h2><?= $pendingCount ?></h2>
                    <p>Waiting for approval</p>
                </div>
            </a>
            
            <a href="notifications.php?filter=approved" class="stat-link <?= $filter==='approved' ? 'active' : '' ?>">
                <div class="stat-card success">
                    <h4>✅ Approved</h4>
                    <h2><?= $approvedCount ?></h2>
                    <p>Ready for pickup</p>
                </div>
            </a>
            
            <a href="notifications.php?filter=rejected" class="stat-link <?= $filter==='rejected' ? 'active' : '' ?>">
                <div class="stat-card danger">
                    <h4>❌ Rejected</h4>
                    <h2><?= $rejectedCount ?></h2>
                    <p>Needs resubmission</p>
                </div>
            </a>
            
            <a href="notifications.php?filter=all" class="stat-link <?= $filter==='all' ? 'active' : '' ?>">
                <div class="stat-card primary">
                    <h4>📬 Total</h4>
                    <h2><?= $totalCount ?></h2>
                    <p>All notifications</p>
                </div>
            </a>
        </div>
        
        <!-- Filter Buttons removed per request -->
        
        <!-- Notifications List -->
        <?php if (empty($displayNotifications)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>No Notifications</h3>
                <p>You don't have any notifications for this filter.</p>
                <?php if($role === 'resident'): ?>
                    <a href="create_request.php" class="btn btn-register">Create New Request</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($displayNotifications as $notif): ?>
                    <div class="notification-card status-<?= $notif['status'] ?>">
                        <div class="notification-header">
                            <h4>
                                <?= ucwords(str_replace('_', ' ', $notif['request_type'])) ?>
                                <?php if($role === 'admin' && isset($notif['full_name'])): ?>
                                    <span style="font-size: 0.8em; opacity: 0.7;"> • <?= htmlspecialchars($notif['full_name']) ?></span>
                                <?php endif; ?>
                            </h4>
                            <span class="status-badge status-<?= $notif['status'] ?>">
                                <?= ucfirst($notif['status']) ?>
                            </span>
                        </div>
                        <div class="notification-body">
                            <p><strong>Verification Code:</strong> <code><?= htmlspecialchars($notif['verification_code']) ?></code></p>
                            <p><strong>Created:</strong> <?= date('M j, Y H:i', strtotime($notif['created_at'])) ?></p>
                        </div>
                        <div class="notification-actions">
                            <?php if($role === 'admin'): ?>
                                <?php if($notif['status'] === 'pending'): ?>
                                    <a href="approve_request.php" class="btn btn-primary btn-sm">⚡ Process Request</a>
                                <?php else: ?>
                                    <span class="status-text">Status: <?= ucfirst($notif['status']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if($notif['status'] === 'approved'): ?>
                                    <a href="view_requests.php" class="btn btn-success btn-sm">📄 View Details</a>
                                <?php elseif($notif['status'] === 'rejected'): ?>
                                    <a href="create_request.php" class="btn btn-warning btn-sm">🔄 Resubmit</a>
                                <?php else: ?>
                                    <span class="status-text">⏳ Waiting for approval</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>