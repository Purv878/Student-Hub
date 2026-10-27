<?php
/**
 * Audit Logging Helper Module
 * Subject: ITUE203 - Web Development Frameworks (Practical 14)
 * Student ID: 25CE089
 */

require_once __DIR__ . '/db.php';

/**
 * Record an administrative or security action in audit_logs
 *
 * @param mysqli|null $conn
 * @param int|null $user_id
 * @param string $action
 * @param string $entity
 * @param int|null $entity_id
 * @param string $details
 * @return bool
 */
function log_audit($conn, $user_id, $action, $entity, $entity_id = null, $details = '') {
    if (!$conn) {
        $conn = getMysqliConnection();
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ississ", $user_id, $action, $entity, $entity_id, $details, $ip);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
    return false;
}
?>
