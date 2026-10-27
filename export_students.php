<?php
/**
 * Practical 11: Export Students to CSV
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
require_role('admin');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

$conn = getMysqliConnection();
$course_filter = trim($_GET['course'] ?? '');

$sql = "SELECT student_id, full_name, email, mobile, department, course, semester, cgpa, gender, status, created_at FROM students";
if (!empty($course_filter)) {
    $sql .= " WHERE course = '" . $conn->real_escape_string($course_filter) . "'";
}
$sql .= " ORDER BY student_id ASC";

$result = $conn->query($sql);

$filename = "studenthub_students_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Output UTF-8 BOM for proper Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Header row
fputcsv($output, ['Student ID', 'Full Name', 'Email', 'Mobile', 'Department', 'Course', 'Semester', 'CGPA', 'Gender', 'Status', 'Created At']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['student_id'],
        $row['full_name'],
        $row['email'],
        $row['mobile'],
        $row['department'],
        $row['course'],
        $row['semester'],
        $row['cgpa'],
        $row['gender'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);

log_audit($conn, $_SESSION['user_id'], 'CSV_EXPORT', 'students', null, "Exported student list to CSV: {$filename}");
exit();
?>
