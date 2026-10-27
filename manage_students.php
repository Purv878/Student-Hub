<?php
/**
 * Practical 11: Student Management CRUD with Search, Filter & Prepared MySQLi Statements
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
require_role('admin');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

$conn = getMysqliConnection();
$admin_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// ----------------------------------------------------
// 1. Handle Delete Operation (with Confirmation & Audit)
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $mode = $_GET['mode'] ?? 'soft'; // soft delete by default as advanced feature

    if ($mode === 'hard') {
        $del_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            log_audit($conn, $admin_id, 'STUDENT_DELETE_HARD', 'students', $del_id, "Permanently deleted student record #{$del_id}");
            $success_msg = "Student record #{$del_id} deleted permanently.";
        } else {
            $error_msg = "Error deleting student: " . $conn->error;
        }
        $del_stmt->close();
    } else {
        // Soft delete: update status to inactive
        $del_stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            log_audit($conn, $admin_id, 'STUDENT_SOFT_DELETE', 'students', $del_id, "Marked student #{$del_id} as inactive (soft delete)");
            $success_msg = "Student marked as inactive (Soft Delete).";
        } else {
            $error_msg = "Error updating status: " . $conn->error;
        }
        $del_stmt->close();
    }
}

// ----------------------------------------------------
// 2. Handle Add / Edit Submission (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error_msg = "CSRF verification failed.";
    } else {
        $edit_id     = (int)($_POST['student_row_id'] ?? 0);
        $student_id  = trim(strtoupper($_POST['student_id'] ?? ''));
        $full_name   = trim($_POST['full_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $mobile      = trim($_POST['mobile'] ?? '');
        $department  = trim($_POST['department'] ?? 'Computer Engineering');
        $course      = trim($_POST['course'] ?? 'B.Tech CE');
        $semester    = (int)($_POST['semester'] ?? 3);
        $cgpa        = (float)($_POST['cgpa'] ?? 8.00);
        $gender      = $_POST['gender'] ?? 'Male';
        $status      = $_POST['status'] ?? 'active';

        // Validation
        if (empty($student_id) || empty($full_name) || empty($email) || empty($mobile)) {
            $error_msg = "Student ID, Name, Email, and Mobile are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please provide a valid email format.";
        } elseif (!preg_match("/^[6-9]\d{9}$/", $mobile)) {
            $error_msg = "Please provide a valid 10-digit mobile number.";
        } else {
            if ($edit_id > 0) {
                // Update Existing Student
                $up_stmt = $conn->prepare("
                    UPDATE students
                    SET student_id = ?, full_name = ?, email = ?, mobile = ?, department = ?, course = ?, semester = ?, cgpa = ?, gender = ?, status = ?
                    WHERE id = ?
                ");
                $up_stmt->bind_param("ssssssids si", $student_id, $full_name, $email, $mobile, $department, $course, $semester, $cgpa, $gender, $status, $edit_id);

                if ($up_stmt->execute()) {
                    log_audit($conn, $admin_id, 'STUDENT_UPDATE', 'students', $edit_id, "Updated details for student {$student_id}");
                    $success_msg = "Student <strong>{$full_name}</strong> updated successfully.";
                } else {
                    $error_msg = "Update failed: " . $conn->error;
                }
                $up_stmt->close();
            } else {
                // Insert New Student
                // Duplicate check
                $chk = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                $chk->bind_param("ss", $student_id, $email);
                $chk->execute();
                if ($chk->get_result()->fetch_assoc()) {
                    $error_msg = "Student ID or Email already exists in records.";
                } else {
                    $ins_stmt = $conn->prepare("
                        INSERT INTO students (student_id, full_name, email, mobile, department, course, semester, cgpa, gender, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins_stmt->bind_param("ssssssidss", $student_id, $full_name, $email, $mobile, $department, $course, $semester, $cgpa, $gender, $status);
                    if ($ins_stmt->execute()) {
                        $new_sid = $conn->insert_id;
                        log_audit($conn, $admin_id, 'STUDENT_CREATE', 'students', $new_sid, "Added new student record: {$student_id} ({$full_name})");
                        $success_msg = "New student <strong>{$full_name}</strong> ({$student_id}) added successfully.";
                    } else {
                        $error_msg = "Insert failed: " . $conn->error;
                    }
                    $ins_stmt->close();
                }
                $chk->close();
            }
        }
    }
}

// ----------------------------------------------------
// 3. Search, Filter, and Pagination Query
// ----------------------------------------------------
$search = trim($_GET['search'] ?? '');
$filter_course = trim($_GET['course'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($filter_course)) {
    $query .= " AND course = ?";
    $params[] = $filter_course;
    $types .= "s";
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrf_token = get_csrf_token();
$page_title = "Practical 11: Student Management CRUD";
$active_nav = "students";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Practical 11: Student Management CRUD</h1>
        <p>Complete Create, Read, Update, and Delete operations with search, course filtering, soft-delete, and CSV export.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="export_students.php<?php echo !empty($filter_course) ? '?course='.urlencode($filter_course) : ''; ?>" class="btn btn-secondary">
            📥 Export to CSV
        </a>
        <button onclick="openStudentModal()" class="btn btn-primary">
            + Add New Student
        </button>
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

<!-- Filter & Search Bar -->
<div class="filter-bar">
    <form method="GET" action="manage_students.php" style="display: flex; gap: 1rem; width: 100%; flex-wrap: wrap;">
        <div class="search-input-wrap">
            <input type="text" name="search" class="form-control" placeholder="Search by name, student ID, or email..." value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <select name="course" class="form-control" style="width: auto;">
            <option value="">All Degree Programs</option>
            <option value="B.Tech CE" <?php echo $filter_course === 'B.Tech CE' ? 'selected' : ''; ?>>B.Tech CE</option>
            <option value="B.Tech IT" <?php echo $filter_course === 'B.Tech IT' ? 'selected' : ''; ?>>B.Tech IT</option>
            <option value="B.Tech EC" <?php echo $filter_course === 'B.Tech EC' ? 'selected' : ''; ?>>B.Tech EC</option>
            <option value="B.Tech ME" <?php echo $filter_course === 'B.Tech ME' ? 'selected' : ''; ?>>B.Tech ME</option>
            <option value="B.Tech CL" <?php echo $filter_course === 'B.Tech CL' ? 'selected' : ''; ?>>B.Tech CL</option>
        </select>

        <select name="status" class="form-control" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($search) || !empty($filter_course) || !empty($filter_status)): ?>
            <a href="manage_students.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Students Records Table -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>Enrolled Students Database</h3>
        <span class="badge badge-info"><?php echo count($students_list); ?> Records Found</span>
    </div>
    <div class="backend-card-body">
        <?php if (empty($students_list)): ?>
            <div class="alert alert-info">
                No matching student records found. Try adjusting your search or filter.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email & Mobile</th>
                            <th>Course & Semester</th>
                            <th>CGPA</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students_list as $st): ?>
                            <tr>
                                <td><strong><code><?php echo htmlspecialchars($st['student_id']); ?></code></strong></td>
                                <td><?php echo htmlspecialchars($st['full_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($st['email']); ?><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($st['mobile']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($st['course']); ?></span><br>
                                    <small style="color: #64748b;">Sem <?php echo htmlspecialchars($st['semester']); ?></small>
                                </td>
                                <td><strong><?php echo number_format($st['cgpa'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($st['gender']); ?></td>
                                <td>
                                    <?php if ($st['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <button onclick='editStudent(<?php echo json_encode($st); ?>)' class="btn btn-secondary btn-sm" title="Edit Student">✏️</button>
                                        <a href="manage_students.php?action=delete&id=<?php echo $st['id']; ?>&mode=soft"
                                           onclick="return confirm('Are you sure you want to soft-delete (deactivate) <?php echo htmlspecialchars($st['full_name']); ?>?');"
                                           class="btn btn-secondary btn-sm" title="Deactivate (Soft Delete)">⏸️</a>
                                        <a href="manage_students.php?action=delete&id=<?php echo $st['id']; ?>&mode=hard"
                                           onclick="return confirm('Permanent Delete: Are you absolutely sure you want to permanently delete this student record?');"
                                           class="btn btn-danger btn-sm" title="Permanent Delete">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="studentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: var(--radius-md); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 id="modalTitle" style="color: var(--primary-color);">Add New Student</h3>
            <button onclick="closeStudentModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="manage_students.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="student_row_id" id="modal_row_id" value="0">

            <div class="form-grid">
                <div class="form-group">
                    <label>Student ID <span class="required">*</span></label>
                    <input type="text" name="student_id" id="modal_student_id" class="form-control" required placeholder="e.g. 25CE089">
                </div>
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" id="modal_full_name" class="form-control" required placeholder="Student Name">
                </div>
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="email" id="modal_email" class="form-control" required placeholder="student@charusat.edu.in">
                </div>
                <div class="form-group">
                    <label>Mobile Number <span class="required">*</span></label>
                    <input type="tel" name="mobile" id="modal_mobile" class="form-control" required placeholder="10 Digits">
                </div>
                <div class="form-group">
                    <label>Course / Program</label>
                    <select name="course" id="modal_course" class="form-control">
                        <option value="B.Tech CE">B.Tech CE</option>
                        <option value="B.Tech IT">B.Tech IT</option>
                        <option value="B.Tech EC">B.Tech EC</option>
                        <option value="B.Tech ME">B.Tech ME</option>
                        <option value="B.Tech CL">B.Tech CL</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="number" name="semester" id="modal_semester" class="form-control" min="1" max="8" value="3">
                </div>
                <div class="form-group">
                    <label>CGPA</label>
                    <input type="number" name="cgpa" id="modal_cgpa" class="form-control" step="0.01" min="0" max="10" value="8.00">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" id="modal_gender" class="form-control">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="modal_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeStudentModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Student Record</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStudentModal() {
    document.getElementById('modalTitle').textContent = "Add New Student";
    document.getElementById('modalSubmitBtn').textContent = "Save Student Record";
    document.getElementById('modal_row_id').value = "0";
    document.getElementById('modal_student_id').value = "";
    document.getElementById('modal_full_name').value = "";
    document.getElementById('modal_email').value = "";
    document.getElementById('modal_mobile').value = "";
    document.getElementById('modal_course').value = "B.Tech CE";
    document.getElementById('modal_semester').value = "3";
    document.getElementById('modal_cgpa').value = "8.00";
    document.getElementById('modal_gender').value = "Male";
    document.getElementById('modal_status').value = "active";
    document.getElementById('studentModal').style.display = "flex";
}

function editStudent(st) {
    document.getElementById('modalTitle').textContent = "Edit Student: " + st.student_id;
    document.getElementById('modalSubmitBtn').textContent = "Update Student";
    document.getElementById('modal_row_id').value = st.id;
    document.getElementById('modal_student_id').value = st.student_id;
    document.getElementById('modal_full_name').value = st.full_name;
    document.getElementById('modal_email').value = st.email;
    document.getElementById('modal_mobile').value = st.mobile;
    document.getElementById('modal_course').value = st.course;
    document.getElementById('modal_semester').value = st.semester;
    document.getElementById('modal_cgpa').value = st.cgpa;
    document.getElementById('modal_gender').value = st.gender;
    document.getElementById('modal_status').value = st.status;
    document.getElementById('studentModal').style.display = "flex";
}

function closeStudentModal() {
    document.getElementById('studentModal').style.display = "none";
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
