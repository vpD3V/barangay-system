<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

// Security Check: Only Admins Can Access
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied - Admin Only");
}

// Fetch ONLY rejected requests with optional type filter
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
$allowedTypes = ['clearance', 'indigency'];

$sql = "SELECT r.*, u.full_name
        FROM requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.status = 'rejected'";
$params = [];

if (in_array($type, $allowedTypes, true)) {
    $sql .= " AND r.request_type = ?";
    $params[] = $type;
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
    <title>Rejected Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <h2>❌ Rejected Requests</h2>

        <?php 
            // Determine active tab
            $activeType = in_array($type, ['clearance','indigency'], true) ? $type : 'all';
        ?>
        <div class="tab-filter" style="margin: 15px 0; display: flex; gap: 8px;">
            <a href="rejected_requests.php" class="btn <?= $activeType === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="rejected_requests.php?type=clearance" class="btn <?= $activeType === 'clearance' ? 'btn-primary' : 'btn-secondary' ?>">Clearance</a>
            <a href="rejected_requests.php?type=indigency" class="btn <?= $activeType === 'indigency' ? 'btn-primary' : 'btn-secondary' ?>">Indigency</a>
        </div>
        
        <div style="margin-bottom: 20px; padding: 15px; background: #fee2e2; color: #991b1b; border-radius: var(--radius-md);">
            <strong>❌ Rejected Requests:</strong> <?= count($requests) ?>
        </div>
        
        <?php if (empty($requests)): ?>
        <div style="text-align: center; padding: 50px; background: var(--bg-light); border-radius: var(--radius-md);">
            <h3>📭 No Rejected Requests Yet</h3>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Resident</th>
                    <th>Type</th>
                    <th>Verification Code</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['full_name']) ?></td>
                    <td><?= htmlspecialchars($req['request_type']) ?></td>
                    <td><strong><?= htmlspecialchars($req['verification_code']) ?></strong></td>
                    <td><?= htmlspecialchars($req['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="btn-group" style="margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>