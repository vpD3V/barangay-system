<?php
// /includes/navigation.php
// Common Navigation Bar for All Pages

// Derive a consistent pending/notification count across pages
if (!isset($pendingCount)) {
    $pendingCount = 0;
    if (isset($_SESSION['role'], $_SESSION['user_id'])) {
        $role = $_SESSION['role'];
        try {
            if ($role === 'resident') {
                // Count this resident's pending requests
                $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM requests WHERE user_id = ? AND status = 'pending'");
                $stmt->execute([$_SESSION['user_id']]);
                $pendingCount = (int) ($stmt->fetch()['cnt'] ?? 0);
            } elseif ($role === 'admin') {
                // Count all pending requests for admin
                $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM requests WHERE status = 'pending'");
                $row = $stmt->fetch();
                $pendingCount = (int) ($row['cnt'] ?? 0);
            }
        } catch (Throwable $e) {
            // Fallback to 0 if DB not available in this context
            $pendingCount = 0;
        }
    }
}
?>

<nav class="nav-bar">
    <div class="nav-left">
        <h2>🏛️ Barangay Digital Clearance System</h2>
    </div>
    
    <div class="nav-right">
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
            <span class="user-role"><?= ucfirst($_SESSION['role']) ?></span>
        </div>
        
        <div class="nav-links">
            <?php if($_SESSION['role'] === 'resident'): ?>
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="create_request.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'create_request.php' ? 'active' : '' ?>">New Request</a>
                <a href="view_requests.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'view_requests.php' ? 'active' : '' ?>">My Requests</a>
                <a href="notifications.php" class="nav-link nav-notif">
                    🔔<?php if ($pendingCount > 0): ?> <span id="notif-badge"><?= $pendingCount ?></span><?php endif; ?>
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['dashboard.php']) ? 'active' : '' ?>">Dashboard</a>
                <a href="approve_request.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['approve_request.php','approved_requests.php','rejected_requests.php']) ? 'active' : '' ?>">Requests</a>
                <a href="notifications.php" class="nav-link nav-notif" title="New requests awaiting review">🔔<?php if ($pendingCount > 0): ?> <span id="notif-badge"><?= $pendingCount ?></span><?php endif; ?></a>
            <?php endif; ?>
            
            <a href="../logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>