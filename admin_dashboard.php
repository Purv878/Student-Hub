<?php
/**
 * Practical 14: Admin Dashboard with RBAC, Dynamic Menu & Audit Log
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
// Restrict strictly to admin role
require_role('admin');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

$conn = getMysqliConnection();
$admin_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// ----------------------------------------------------
// Handle User Status Toggle (Intermediate Requirement)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "CSRF verification failed.";
    } else {
        $target_uid = (int)$_POST['toggle_user_id'];
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';

        // Cannot deactivate self
        if ($target_uid === $admin_id) {
            $error_msg = "Self-deactivation is prohibited for the primary administrator.";
        } else {
            $up = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $up->bind_param("si", $new_status, $target_uid);
            if ($up->execute()) {
                log_audit($conn, $admin_id, 'USER_STATUS_TOGGLE', 'users', $target_uid, "Changed account status of user #{$target_uid} to '{$new_status}'");
                $success_msg = "User #{$target_uid} status successfully updated to '{$new_status}'.";
            } else {
                $error_msg = "Error updating status: " . $conn->error;
            }
            $up->close();
        }
    }
}

// ----------------------------------------------------
// Dynamic Statistical Counts (Aggregation Queries)
// ----------------------------------------------------
$total_students = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'] ?? 0;
$total_events = $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'] ?? 0;
$total_registrations = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'] ?? 0;
$active_users = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status = 'active'")->fetch_assoc()['c'] ?? 0;

// Category distribution for Analytics Chart
$cat_query = $conn->query("SELECT category, COUNT(*) as cnt FROM events GROUP BY category");
$category_counts = [];
while ($row = $cat_query->fetch_assoc()) {
    $category_counts[$row['category']] = (int)$row['cnt'];
}

// ----------------------------------------------------
// Latest 5 Student Registrations
// ----------------------------------------------------
$latest_regs = $conn->query("
    SELECT r.id, r.registration_date, r.attendance_status, s.student_id, s.full_name, e.title AS event_title, e.category
    FROM registrations r
    JOIN students s ON r.student_id = s.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registration_date DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// ----------------------------------------------------
// Latest 8 Audit Log Entries (Traceability)
// ----------------------------------------------------
$audit_entries = $conn->query("
    SELECT a.*, COALESCE(u.username, 'System') AS actor
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ----------------------------------------------------
// User Accounts List (for Status Toggle demonstration)
// ----------------------------------------------------
$users_list = $conn->query("SELECT id, username, email, role, status, last_login FROM users ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$csrf_token = get_csrf_token();
$page_title = "Practical 14: Role-Based Admin Dashboard & Audit Log";
$active_nav = "dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Practical 14: Central Administration Dashboard</h1>
        <p>Live metrics aggregation, role-based access control (RBAC), user moderation, and complete audit logging.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <span class="badge badge-success" style="font-size: 0.95rem; padding: 0.5rem 0.85rem;">
            🛡️ Role: Super Administrator
        </span>
        <a href="manage_students.php" class="btn btn-primary btn-sm">+ Manage Students</a>
        <a href="manage_events.php" class="btn btn-secondary btn-sm">+ Manage Events</a>
    </div>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success">
        <span>✅</span>
        <div><?php echo $success_msg; ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger">
        <span>⚠️</span>
        <div><?php echo $error_msg; ?></div>
    </div>
<?php endif; ?>

<!-- Dynamic Statistical KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👨‍🎓</div>
        <div class="stat-info">
            <h4>Total Students</h4>
            <div class="stat-value"><?php echo $total_students; ?></div>
            <small style="color: #64748b;">Enrolled in CSPIT</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">📅</div>
        <div class="stat-info">
            <h4>Campus Events</h4>
            <div class="stat-value"><?php echo $total_events; ?></div>
            <small style="color: #64748b;">Active & Completed</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">📝</div>
        <div class="stat-info">
            <h4>Total Registrations</h4>
            <div class="stat-value"><?php echo $total_registrations; ?></div>
            <small style="color: #64748b;">Event Applications</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">👥</div>
        <div class="stat-info">
            <h4>Active User Accounts</h4>
            <div class="stat-value"><?php echo $active_users; ?></div>
            <small style="color: #64748b;">Verified Logins</small>
        </div>
    </div>
</div>

<!-- Layout Grid: Registrations & Events Analytics -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- Recent Registrations -->
    <div class="backend-card" style="margin-bottom: 0;">
        <div class="backend-card-header">
            <h3>Recent Student Event Enrollments</h3>
            <span class="badge badge-primary">Latest Activity</span>
        </div>
        <div class="backend-card-body">
            <div class="table-responsive">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Target Event</th>
                            <th>Category</th>
                            <th>Enrolled On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_regs as $reg): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($reg['full_name']); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($reg['student_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($reg['category']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
                                <td><span class="badge badge-success"><?php echo ucfirst($reg['attendance_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Analytics Chart: Category Distribution -->
    <div class="backend-card" style="margin-bottom: 0;">
        <div class="backend-card-header">
            <h3>Event Analytics</h3>
            <span class="badge badge-info">Category Mix</span>
        </div>
        <div class="backend-card-body">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php
                $colors = ['Technical' => '#2196f3', 'Workshop' => '#9c27b0', 'Sports' => '#ff9800', 'Cultural' => '#4caf50'];
                foreach ($category_counts as $cat_name => $count):
                    $pct = $total_events > 0 ? round(($count / $total_events) * 100) : 0;
                    $c_color = $colors[$cat_name] ?? '#607d8b';
                ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 4px;">
                            <strong><?php echo htmlspecialchars($cat_name); ?></strong>
                            <span><?php echo $count; ?> events (<?php echo $pct; ?>%)</span>
                        </div>
                        <div style="background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden;">
                            <div style="background: <?php echo $c_color; ?>; width: <?php echo $pct; ?>%; height: 100%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem;">
                <strong>RBAC Architecture:</strong><br>
                • Admins have full CRUD & Audit access.<br>
                • Students are redirected to personal portal.<br>
                • Direct URL tampering triggers 403 Forbidden.
            </div>
        </div>
    </div>
</div>

<!-- Moderation & User Accounts with Status Toggle (Intermediate Feature) -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>User Management & Status Moderation (Intermediate Extension)</h3>
        <span class="badge badge-info"><?php echo count($users_list); ?> Registered Accounts</span>
    </div>
    <div class="backend-card-body">
        <div class="table-responsive">
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Current Status</th>
                        <th>Moderation Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $u): ?>
                        <tr>
                            <td><code>#<?php echo $u['id']; ?></code></td>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo !empty($u['last_login']) ? date('M d, H:i', strtotime($u['last_login'])) : 'Never'; ?></td>
                            <td>
                                <span class="badge <?php echo $u['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['id'] !== $admin_id): ?>
                                    <form method="POST" action="admin_dashboard.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="toggle_user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $u['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                            <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: #64748b;">Primary Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Audit Log Table (Traceability & Security) -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>System Audit & Activity Log (Security Traceability)</h3>
        <span class="badge badge-warning">Immutable Audit Trail</span>
    </div>
    <div class="backend-card-body">
        <div class="table-responsive">
            <table class="backend-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action Performed</th>
                        <th>Target Entity</th>
                        <th>Details & Reason</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_entries as $log): ?>
                        <tr>
                            <td><code>#<?php echo $log['id']; ?></code></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['actor']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><code><?php echo htmlspecialchars($log['entity']) . ($log['entity_id'] ? " #{$log['entity_id']}" : ''); ?></code></td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                            <td><small style="color: #64748b;"><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
