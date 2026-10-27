<?php
/**
 * Practical 10: Secure Login with Sessions, RBAC, Timeout & Remember-Me Token
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: student_portal.php");
        exit();
    }
}

$error_msg = "";
$info_msg = "";

// Check for URL status messages
$msg = $_GET['msg'] ?? '';
if ($msg === 'logged_out') {
    $info_msg = "You have been logged out securely. Session destroyed.";
} elseif ($msg === 'timeout') {
    $error_msg = "Your session expired due to inactivity (" . (SESSION_TIMEOUT_SECONDS / 60) . " min timeout). Please sign in again.";
} elseif ($msg === 'auth_required') {
    $error_msg = "Please log in to access the protected portal page.";
}

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error_msg = "CSRF verification failed. Please try submitting again.";
    } else {
        $login_identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);

        if (empty($login_identifier) || empty($password)) {
            $error_msg = "Please enter both username/email and password.";
        } else {
            $conn = getMysqliConnection();
            $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE (username = ? OR email = ?) LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $login_identifier, $login_identifier);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($user = $res->fetch_assoc()) {
                    if ($user['status'] !== 'active') {
                        $error_msg = "This account is inactive. Please contact the administrator.";
                        log_audit($conn, $user['id'], 'LOGIN_BLOCKED', 'users', $user['id'], 'Login attempt on inactive account.');
                    } elseif (password_verify($password, $user['password_hash'])) {
                        // Password matches! Proceed to secure login
                        login_user($user, $remember);
                        log_audit($conn, $user['id'], 'LOGIN_SUCCESS', 'users', $user['id'], "User logged in with role {$user['role']}.");

                        // Role-based redirection
                        if ($user['role'] === 'admin') {
                            header("Location: admin_dashboard.php");
                        } else {
                            header("Location: student_portal.php");
                        }
                        exit();
                    } else {
                        $error_msg = "Invalid credentials. Password verification failed.";
                        log_audit($conn, $user['id'], 'LOGIN_FAILED', 'users', $user['id'], 'Incorrect password provided.');
                    }
                } else {
                    $error_msg = "No user found with the provided username or email.";
                    log_audit($conn, null, 'LOGIN_FAILED', 'users', null, "Failed login for identifier: {$login_identifier}");
                }
                $stmt->close();
            }
        }
    }
}

$csrf_token = get_csrf_token();
$page_title = "Practical 10: Secure Login & Authentication";
$active_nav = "login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <img src="Logo.jpg" alt="StudentHub">
            <h2>Practical 10: Secure Login</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                PHP Sessions, <code>password_verify()</code>, RBAC, Timeout, & Remember-Me.
            </p>
        </div>

        <?php if (!empty($info_msg)): ?>
            <div class="alert alert-info">
                <span>ℹ️</span>
                <div><?php echo htmlspecialchars($info_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?php echo htmlspecialchars($error_msg); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="secure_login.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="identifier">Username or Email Address <span class="required">*</span></label>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       placeholder="e.g. admin or purv_ce" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Enter your account password" required>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: normal;">
                    <input type="checkbox" name="remember_me" value="1" style="width: 16px; height: 16px;">
                    Remember me on this device (30 Days)
                </label>
                <span class="badge badge-info" title="Random crypto token stored in DB">Safe Token</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                🔑 Authenticate & Log In
            </button>
        </form>

        <!-- Demo accounts box -->
        <div style="margin-top: 1.5rem; background: #f8fafc; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); padding: 1rem; font-size: 0.85rem;">
            <strong style="color: var(--primary-color);">Test Demonstration Accounts:</strong>
            <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                <div>
                    <strong>Admin Role:</strong><br>
                    User: <code>admin</code><br>
                    Pass: <code>Admin@123</code>
                </div>
                <div>
                    <strong>Student Role:</strong><br>
                    User: <code>purv_ce</code><br>
                    Pass: <code>Student@123</code>
                </div>
            </div>
        </div>

        <div class="auth-footer">
            New to StudentHub? <a href="secure_register.php">Create Student Account (Pr 9) &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
