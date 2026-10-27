<?php
/**
 * 403 Forbidden / Access Denied Page
 * Subject: ITUE203 - Web Development Frameworks (Practical 10 & 14)
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();

http_response_code(403);
$page_title = "Access Denied (403)";
$active_nav = "";
require_once __DIR__ . '/includes/header.php';
?>

<div class="backend-card" style="max-width: 650px; margin: 3rem auto; text-align: center; border-top: 4px solid var(--danger-color);">
    <div class="backend-card-body" style="padding: 3rem 2rem;">
        <div style="font-size: 4rem; color: var(--danger-color); margin-bottom: 1rem;">🚫</div>
        <h1 style="color: var(--danger-color); font-size: 2rem; margin-bottom: 0.5rem;">403 — Access Denied</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 1.5rem;">
            You do not have permission to access this resource. This administrative area is restricted to authorized personnel with administrator privileges.
        </p>

        <div class="alert alert-warning" style="text-align: left; margin-bottom: 2rem;">
            <strong>RBAC Enforcement Notice:</strong><br>
            Current User: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Anonymous'); ?></strong><br>
            Current Role: <strong><?php echo htmlspecialchars($_SESSION['role'] ?? 'Unauthenticated'); ?></strong><br>
            Required Role: <strong>Administrator</strong>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                <a href="student_portal.php" class="btn btn-primary">Return to Student Portal</a>
            <?php else: ?>
                <a href="secure_login.php" class="btn btn-primary">Sign in with Admin Account</a>
            <?php endif; ?>
            <a href="index.html" class="btn btn-secondary">StudentHub Home</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
