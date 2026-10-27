<?php
/**
 * Practical 10: Authenticated Student Portal & Dashboard
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$uid = $_SESSION['user_id'];
$conn = getMysqliConnection();

// Fetch student details
$stud_stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ? LIMIT 1");
$stud_stmt->bind_param("i", $uid);
$stud_stmt->execute();
$student = $stud_stmt->get_result()->fetch_assoc();
$stud_stmt->close();

// Fallback in case admin is viewing or user hasn't student row yet
if (!$student) {
    $student = [
        'student_id' => '25CE089',
        'full_name'  => $_SESSION['username'],
        'department' => 'Computer Engineering',
        'course'     => 'B.Tech CE',
        'semester'   => 3,
        'cgpa'       => 9.45,
        'email'      => $_SESSION['email'],
        'mobile'     => '9876543210',
        'status'     => 'active'
    ];
}

// Fetch events registered by this student
$reg_stmt = $conn->prepare("
    SELECT e.id, e.title, e.category, e.event_date, e.event_time, e.venue, r.registration_date, r.attendance_status
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN students s ON r.student_id = s.id
    WHERE s.user_id = ? OR s.student_id = ?
    ORDER BY e.event_date DESC
");
$reg_stmt->bind_param("is", $uid, $student['student_id']);
$reg_stmt->execute();
$my_events = $reg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reg_stmt->close();

$page_title = "Student Portal Dashboard";
$active_nav = "student_portal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Welcome, <?php echo htmlspecialchars($student['full_name']); ?> 👋</h1>
        <p>Student Portal (Practical 10 RBAC) &bull; ID: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong> &bull; Semester <?php echo htmlspecialchars($student['semester']); ?></p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <span class="badge badge-success">Role: Student</span>
        <span class="badge badge-info">Session: Active & Verified</span>
    </div>
</div>

<!-- Student Stats Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">🎓</div>
        <div class="stat-info">
            <h4>Academic Standing (CGPA)</h4>
            <div class="stat-value"><?php echo number_format($student['cgpa'], 2); ?></div>
            <small style="color: #64748b;"><?php echo htmlspecialchars($student['course']); ?></small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📅</div>
        <div class="stat-info">
            <h4>Registered Events</h4>
            <div class="stat-value"><?php echo count($my_events); ?></div>
            <small style="color: #64748b;">Enrolled Campus Activities</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📊</div>
        <div class="stat-info">
            <h4>Semester Attendance</h4>
            <div class="stat-value">91.4%</div>
            <small style="color: #64748b;">Above 80% Mandatory Target</small>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🛡️</div>
        <div class="stat-info">
            <h4>Account Status</h4>
            <div class="stat-value" style="font-size: 1.25rem; color: var(--success-color);">Verified Active</div>
            <small style="color: #64748b;">Dept: <?php echo htmlspecialchars($student['department']); ?></small>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Profile Summary -->
    <div class="backend-card">
        <div class="backend-card-header">
            <h3>Student Profile</h3>
            <span class="badge badge-info"><?php echo htmlspecialchars($student['student_id']); ?></span>
        </div>
        <div class="backend-card-body" style="font-size: 0.92rem;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <img src="Logo.jpg" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--primary-light);">
                <h4 style="margin-top: 0.5rem; color: var(--primary-color);"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                <p style="color: #64748b;"><?php echo htmlspecialchars($student['course']); ?> &bull; Sem <?php echo htmlspecialchars($student['semester']); ?></p>
            </div>

            <div style="line-height: 2; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <div><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></div>
                <div><strong>Mobile:</strong> <?php echo htmlspecialchars($student['mobile']); ?></div>
                <div><strong>Department:</strong> <?php echo htmlspecialchars($student['department']); ?></div>
                <div><strong>Institution:</strong> CSPIT, CHARUSAT</div>
                <div><strong>Current Role:</strong> <code><?php echo htmlspecialchars($_SESSION['role']); ?></code></div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="pr7_contact.php" class="btn btn-secondary btn-sm">Submit Academic Inquiry</a>
                <a href="ajax_events_manager.php" class="btn btn-primary btn-sm">Explore Campus Events (AJAX)</a>
            </div>
        </div>
    </div>

    <!-- My Registered Events -->
    <div class="backend-card">
        <div class="backend-card-header">
            <h3>My Event Registrations</h3>
            <span class="badge badge-primary"><?php echo count($my_events); ?> Activities</span>
        </div>
        <div class="backend-card-body">
            <?php if (empty($my_events)): ?>
                <div class="alert alert-info">
                    You have not registered for any events yet. Check out the <a href="ajax_events_manager.php">Events Catalogue</a> to enroll!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="backend-table">
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Category</th>
                                <th>Date & Time</th>
                                <th>Venue</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_events as $ev): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($ev['event_date'])); ?><br>
                                        <small style="color: #64748b;"><?php echo date('h:i A', strtotime($ev['event_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                    <td>
                                        <?php if ($ev['attendance_status'] === 'attended'): ?>
                                            <span class="badge badge-success">Attended</span>
                                        <?php else: ?>
                                            <span class="badge badge-primary">Registered</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
