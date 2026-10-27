<?php
/**
 * Shared Header Template for Backend Pages
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

if (!isset($page_title)) {
    $page_title = "StudentHub Portal";
}
if (!isset($active_nav)) {
    $active_nav = "";
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — StudentHub</title>
    <link rel="stylesheet" href="css/backend.css">
    <link rel="icon" type="image/jpeg" href="Logo.jpg">
</head>
<body>
    <header class="backend-navbar">
        <div class="backend-nav-container">
            <a href="admin_dashboard.php" class="backend-brand">
                <img src="Logo.jpg" alt="StudentHub">
                <div>
                    <div>StudentHub <span class="badge-practical">WDF Backend</span></div>
                    <small style="font-size: 0.72rem; color: #64748b; font-weight: normal;">ID: 25CE089 | CSPIT-CE</small>
                </div>
            </a>

            <nav>
                <ul class="backend-nav-links">
                    <li><a href="index.html" title="Return to Static StudentHub">🏠 Static Portal</a></li>

                    <?php if ($is_logged_in && $user_role === 'admin'): ?>
                        <li><a href="admin_dashboard.php" class="<?php echo $active_nav === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="manage_students.php" class="<?php echo $active_nav === 'students' ? 'active' : ''; ?>">Students (Pr 11)</a></li>
                        <li><a href="manage_events.php" class="<?php echo $active_nav === 'events' ? 'active' : ''; ?>">Events (Pr 12)</a></li>
                        <li><a href="ajax_events_manager.php" class="<?php echo $active_nav === 'ajax' ? 'active' : ''; ?>">AJAX API (Pr 13)</a></li>
                        <li><a href="pr7_records.php" class="<?php echo $active_nav === 'pr7' ? 'active' : ''; ?>">CSV/JSON (Pr 7)</a></li>
                        <li><a href="test_db.php" class="<?php echo $active_nav === 'db' ? 'active' : ''; ?>">DB Status (Pr 8)</a></li>
                    <?php elseif ($is_logged_in && $user_role === 'student'): ?>
                        <li><a href="student_portal.php" class="<?php echo $active_nav === 'student_portal' ? 'active' : ''; ?>">My Dashboard</a></li>
                        <li><a href="ajax_events_manager.php" class="<?php echo $active_nav === 'ajax' ? 'active' : ''; ?>">Campus Events</a></li>
                        <li><a href="pr7_contact.php" class="<?php echo $active_nav === 'contact' ? 'active' : ''; ?>">Submit Feedback</a></li>
                    <?php else: ?>
                        <li><a href="pr7_contact.php" class="<?php echo $active_nav === 'contact' ? 'active' : ''; ?>">Form Processor (Pr 7)</a></li>
                        <li><a href="test_db.php" class="<?php echo $active_nav === 'db' ? 'active' : ''; ?>">DB Connect (Pr 8)</a></li>
                        <li><a href="secure_register.php" class="<?php echo $active_nav === 'register' ? 'active' : ''; ?>">Register (Pr 9)</a></li>
                        <li><a href="secure_login.php" class="<?php echo $active_nav === 'login' ? 'active' : ''; ?>">Login (Pr 10)</a></li>
                        <li><a href="ajax_events_manager.php" class="<?php echo $active_nav === 'ajax' ? 'active' : ''; ?>">AJAX View (Pr 13)</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="backend-user-menu">
                <?php if ($is_logged_in): ?>
                    <span class="backend-user-badge">
                        👤 <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($user_role); ?>)
                    </span>
                    <a href="logout.php" class="btn-nav-logout">Logout</a>
                <?php else: ?>
                    <a href="secure_login.php" class="btn btn-primary btn-sm">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="backend-container">
