<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

$search = $_GET['search'] ?? '';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';

// 1. Handle Delete/Remove Request (CRUD: Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    
    // Only allow residents to delete their own 'pending' requests
    if ($role === 'resident') {
        // Check if the request exists, belongs to the user, and is pending
        $checkStmt = $pdo->prepare("SELECT id FROM requests WHERE id = ? AND user_id = ? AND status = 'pending'");
        $checkStmt->execute([$deleteId, $userId]);
        
        if ($checkStmt->rowCount() > 0) {
            $delStmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
            $delStmt->execute([$deleteId]);
            $success = "Request removed/cancelled successfully.";
        } else {
            $error = "Cannot remove request. It may have been processed already or does not exist.";
        }
    }
}

// Build query based on role
if ($role === 'admin') {
    $sql = "SELECT r.*, u.full_name FROM requests r JOIN users u ON r.user_id = u.id";
    $params = [];
} else {
    $sql = "SELECT r.*, u.full_name FROM requests r JOIN users u ON r.user_id = u.id WHERE r.user_id = ?";
    $params = [$userId];
}

// Add search filter
if ($search) {
    $sql .= " AND (r.request_type LIKE ? OR r.verification_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Barangay System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h2><?= $role === 'admin' ? '📋 All Requests' : '📝 My Requests' ?></h2>
            <p>View and track your clearance requests</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Search Form -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="🔍 Search by Type or Code..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        
        <!-- Requests Table -->
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <h3>No requests found</h3>
                <p><?= $role === 'admin' ? 'No requests in the system.' : 'Create your first request to get started.' ?></p>
                <?php if($role === 'resident'): ?>
                    <a href="create_request.php" class="btn btn-register">Create New Request</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if($role === 'admin'): ?>
                                <th>Resident</th>
                            <?php endif; ?>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Verification Code</th>
                            <th>Date Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong>#<?= $req['id'] ?></strong></td>
                            <?php if($role === 'admin'): ?>
                                <td><?= htmlspecialchars($req['full_name']) ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="request-type"><?= ucwords(str_replace('_', ' ', $req['request_type'])) ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $req['status'] ?>">
                                    <?= ucfirst($req['status']) ?>
                                </span>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($req['verification_code']) ?></code>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($req['created_at'])) ?></td>
                            <td>
                                <?php if($role === 'resident' && $req['status'] === 'pending'): ?>
                                    <!-- Delete Button Form -->
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel and remove this request?');">
                                        <input type="hidden" name="delete_id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="btn" style="background-color: #ef4444; color: white; padding: 5px 10px; font-size: 0.8em; border:none; cursor:pointer; border-radius:4px;">🗑️ Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>