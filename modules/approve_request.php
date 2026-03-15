<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

// Security Check: Only Admins Can Access
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied - Admin Only");
}

$success = '';
$error = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    try {
        if ($status === 'approved') {
            // Generate an 8-char uppercase hex code if not already present
            $code = strtoupper(bin2hex(random_bytes(4)));
            $stmt = $pdo->prepare(
                "UPDATE requests 
                 SET status = ?, 
                     verification_code = CASE 
                         WHEN verification_code IS NULL OR verification_code = '' THEN ? 
                         ELSE verification_code 
                     END 
                 WHERE id = ?"
            );
            $stmt->execute([$status, $code, $id]);
        } elseif ($status === 'rejected') {
            // Clear any existing verification code on rejection
            $stmt = $pdo->prepare("UPDATE requests SET status = ?, verification_code = NULL WHERE id = ?");
            $stmt->execute([$status, $id]);
        } else {
            // Fallback for other statuses (if any)
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
        
        if ($stmt->rowCount() > 0) {
            $success = "Request " . ($status === 'approved' ? 'Approved' : 'Rejected') . " successfully!";
        } else {
            $error = "No changes made or record not found";
        }
    } catch (Throwable $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch ONLY pending requests for admin with optional type filter
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
$allowedTypes = ['clearance', 'indigency'];

$sql = "SELECT r.*, u.full_name, u.username
        FROM requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.status = 'pending'";
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
    <title>Pending Requests - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>
    
    <div class="content">
        <h2>📋 Pending Requests Queue</h2>

        <?php 
            // Determine active tab
            $activeType = in_array($type, ['clearance','indigency'], true) ? $type : 'all';
        ?>
        <div class="tab-filter" style="margin: 15px 0; display: flex; gap: 8px;">
            <a href="approve_request.php" class="btn <?= $activeType === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
            <a href="approve_request.php?type=clearance" class="btn <?= $activeType === 'clearance' ? 'btn-primary' : 'btn-secondary' ?>">Clearance</a>
            <a href="approve_request.php?type=indigency" class="btn <?= $activeType === 'indigency' ? 'btn-primary' : 'btn-secondary' ?>">Indigency</a>
        </div>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div style="margin-bottom: 20px; padding: 15px; background: #fef3c7; color: #92400e; border-radius: var(--radius-md);">
            <strong>⚠️ Pending Requests:</strong> <?= count($requests) ?>
        </div>
        
        <?php if (empty($requests)): ?>
        <div style="text-align: center; padding: 50px; background: var(--bg-light); border-radius: var(--radius-md);">
            <h3>🎉 No Pending Requests!</h3>
            <p>All requests have been processed.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Resident Name</th>
                    <th>Username</th>
                    <th>Type</th>
                    <th>Verification Code</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['full_name']) ?></td>
                    <td><?= htmlspecialchars($req['username']) ?></td>
                    <td><?= htmlspecialchars($req['request_type']) ?></td>
                    <td><strong><?= htmlspecialchars($req['verification_code']) ?></strong></td>
                    <td><?= htmlspecialchars($req['created_at']) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $req['id'] ?>">
                            <input type="hidden" name="action" value="update">
                            <select name="status" style="width: 130px; padding: 8px;">
                                <option value="approved">✅ Approve</option>
                                <option value="rejected">❌ Reject</option>
                            </select>
                            <button type="submit" style="padding: 8px 15px;">Submit</button>
                        </form>
                    </td>
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