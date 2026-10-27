<?php
/**
 * Authentication and Session Management Module
 * Subject: ITUE203 - Web Development Frameworks (Practical 10)
 * Student ID: 25CE089
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

// Session configuration constants
define('SESSION_TIMEOUT_SECONDS', 900); // 15 minutes
define('REMEMBER_COOKIE_NAME', 'studenthub_remember');
define('REMEMBER_COOKIE_EXPIRY', 30 * 24 * 3600); // 30 days

/**
 * Initialize secure session with cookie security parameters
 */
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure cookie flags
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Session Timeout Check for logged-in users
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
            $timed_out_uid = $_SESSION['user_id'];
            log_audit(null, $timed_out_uid, 'SESSION_TIMEOUT', 'users', $timed_out_uid, 'User session timed out due to inactivity.');
            logout_user(false);
            header("Location: secure_login.php?msg=timeout");
            exit();
        }
        $_SESSION['last_activity'] = time();
    } else {
        // Attempt Remember-Me auto login if cookie is present
        check_remember_token();
    }
}

/**
 * Check and validate remember-me cookie
 */
function check_remember_token() {
    if (isset($_COOKIE[REMEMBER_COOKIE_NAME]) && !empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        $token = $_COOKIE[REMEMBER_COOKIE_NAME];
        $hashed_token = hash('sha256', $token);

        $conn = getMysqliConnection();
        $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE remember_token = ? AND status = 'active' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $hashed_token);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($user = $res->fetch_assoc()) {
                login_user($user, false);
                log_audit($conn, $user['id'], 'REMEMBER_LOGIN', 'users', $user['id'], 'Automatic authentication via remember-me token.');
            }
            $stmt->close();
        }
    }
}

/**
 * Authenticate and log in a user
 *
 * @param array $user
 * @param bool $remember
 */
function login_user($user, $remember = false) {
    if (session_status() === PHP_SESSION_NONE) {
        init_session();
    }

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    $conn = getMysqliConnection();

    // Update last login timestamp
    $up_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    if ($up_stmt) {
        $up_stmt->bind_param("i", $user['id']);
        $up_stmt->execute();
        $up_stmt->close();
    }

    // Handle Remember-Me Token
    if ($remember) {
        $random_token = bin2hex(random_bytes(32));
        $hashed_token = hash('sha256', $random_token);

        $token_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        if ($token_stmt) {
            $token_stmt->bind_param("si", $hashed_token, $user['id']);
            $token_stmt->execute();
            $token_stmt->close();

            setcookie(REMEMBER_COOKIE_NAME, $random_token, [
                'expires'  => time() + REMEMBER_COOKIE_EXPIRY,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
}

/**
 * Log out user, invalidate session and remove cookies
 *
 * @param bool $redirect
 */
function logout_user($redirect = true) {
    if (session_status() === PHP_SESSION_NONE) {
        init_session();
    }

    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $conn = getMysqliConnection();
        $token_stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        if ($token_stmt) {
            $token_stmt->bind_param("i", $uid);
            $token_stmt->execute();
            $token_stmt->close();
        }
        log_audit($conn, $uid, 'LOGOUT', 'users', $uid, 'User initiated logout.');
    }

    // Remove remember-me cookie
    if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
        setcookie(REMEMBER_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE[REMEMBER_COOKIE_NAME]);
    }

    // Clear session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    if ($redirect) {
        header("Location: secure_login.php?msg=logged_out");
        exit();
    }
}

/**
 * Enforce authentication
 */
function require_login() {
    init_session();
    if (!isset($_SESSION['user_id'])) {
        header("Location: secure_login.php?msg=auth_required");
        exit();
    }
}

/**
 * Enforce role-based access control (RBAC)
 *
 * @param array|string $allowed_roles
 */
function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
        header("Location: unauthorized.php");
        exit();
    }
}

/**
 * CSRF Protection
 */
function get_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        init_session();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        init_session();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
?>
