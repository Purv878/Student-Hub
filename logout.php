<?php
/**
 * Practical 10: Secure Logout Handler
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();

logout_user(true);
?>
