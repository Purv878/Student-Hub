<?php
/**
 * Practical 9: Secure User Registration with Database Insert, Duplicate Check & Password Hashing
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/audit.php';

$errors = [];
$success_msg = "";
$form_data = [
    'full_name'   => '',
    'student_id'  => '',
    'username'    => '',
    'email'       => '',
    'mobile'      => '',
    'department'  => 'Computer Engineering',
    'course'      => 'B.Tech CE',
    'semester'    => 3,
    'gender'      => 'Male'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors['general'] = "Security validation failed. Invalid or expired session token.";
    }

    // 2. Capture and Sanitize Inputs
    $form_data['full_name']  = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['student_id'] = trim(strtoupper(filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''));
    $form_data['username']   = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['email']      = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $form_data['mobile']     = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['department'] = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['course']     = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['semester']   = (int)($_POST['semester'] ?? 3);
    $form_data['gender']     = $_POST['gender'] ?? 'Male';
    $password                = $_POST['password'] ?? '';
    $confirm_password        = $_POST['confirm_password'] ?? '';

    // 3. Validation Logic
    if (empty($form_data['full_name']) || !preg_match("/^[A-Za-z\s]{3,60}$/", $form_data['full_name'])) {
        $errors['full_name'] = "Full name must be 3-60 letters.";
    }

    if (empty($form_data['student_id']) || !preg_match("/^[A-Z0-9]{5,15}$/", $form_data['student_id'])) {
        $errors['student_id'] = "Student ID must be 5-15 alphanumeric characters (e.g., 25CE089).";
    }

    if (empty($form_data['username']) || !preg_match("/^[a-zA-Z0-9_]{3,25}$/", $form_data['username'])) {
        $errors['username'] = "Username must be 3-25 alphanumeric characters or underscores.";
    }

    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "A valid email format is required.";
    }

    if (empty($form_data['mobile']) || !preg_match("/^[6-9]\d{9}$/", $form_data['mobile'])) {
        $errors['mobile'] = "Mobile must be a valid 10-digit number.";
    }

    // Password strength verification
    if (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password)) {
        $errors['password'] = "Password must include at least one uppercase letter, one lowercase letter, and one number.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // 4. Duplicate Check using Prepared Statements
    if (empty($errors)) {
        $conn = getMysqliConnection();

        // Check if username or email already exists in users table
        $dup_user = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $dup_user->bind_param("ss", $form_data['username'], $form_data['email']);
        $dup_user->execute();
        $dup_user_res = $dup_user->get_result();

        if ($existing = $dup_user_res->fetch_assoc()) {
            if (strtolower($existing['username']) === strtolower($form_data['username'])) {
                $errors['username'] = "Username '" . htmlspecialchars($form_data['username']) . "' is already taken.";
            }
            if (strtolower($existing['email']) === strtolower($form_data['email'])) {
                $errors['email'] = "Email '" . htmlspecialchars($form_data['email']) . "' is already registered.";
            }
        }
        $dup_user->close();

        // Check if student_id already exists in students table
        $dup_stud = $conn->prepare("SELECT id FROM students WHERE student_id = ? LIMIT 1");
        $dup_stud->bind_param("s", $form_data['student_id']);
        $dup_stud->execute();
        if ($dup_stud->get_result()->fetch_assoc()) {
            $errors['student_id'] = "Student ID '" . htmlspecialchars($form_data['student_id']) . "' is already registered.";
        }
        $dup_stud->close();
    }

    // 5. Safe Insertion with Transaction & Password Hashing
    if (empty($errors)) {
        $conn = getMysqliConnection();
        $conn->begin_transaction();

        try {
            // Password Hashing via BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

            // Insert User
            $user_role = 'student';
            $user_status = 'active';
            $insert_user = $conn->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
            $insert_user->bind_param("sssss", $form_data['username'], $form_data['email'], $hashed_password, $user_role, $user_status);
            $insert_user->execute();
            $new_user_id = $conn->insert_id;
            $insert_user->close();

            // Insert Student Record
            $cgpa_default = 8.00;
            $insert_stud = $conn->prepare("INSERT INTO students (user_id, student_id, full_name, email, mobile, department, course, semester, cgpa, gender, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stud->bind_param(
                "issssssidss",
                $new_user_id,
                $form_data['student_id'],
                $form_data['full_name'],
                $form_data['email'],
                $form_data['mobile'],
                $form_data['department'],
                $form_data['course'],
                $form_data['semester'],
                $cgpa_default,
                $form_data['gender'],
                $user_status
            );
            $insert_stud->execute();
            $insert_stud->close();

            // Audit log
            log_audit($conn, $new_user_id, 'REGISTRATION_SUCCESS', 'users', $new_user_id, "Registered new student account: {$form_data['student_id']}");

            $conn->commit();
            $success_msg = "Account created successfully for <strong>" . htmlspecialchars($form_data['full_name']) . "</strong> (ID: " . htmlspecialchars($form_data['student_id']) . ")! Passwords securely hashed with Bcrypt.";

            // Reset form
            $form_data = [
                'full_name'   => '',
                'student_id'  => '',
                'username'    => '',
                'email'       => '',
                'mobile'      => '',
                'department'  => 'Computer Engineering',
                'course'      => 'B.Tech CE',
                'semester'    => 3,
                'gender'      => 'Male'
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = "Registration failed due to a database error: " . $e->getMessage();
        }
    }
}

$csrf_token = get_csrf_token();
$page_title = "Practical 9: Secure User Registration";
$active_nav = "register";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper" style="padding-top: 1rem;">
    <div class="auth-card" style="max-width: 680px;">
        <div class="auth-header">
            <img src="Logo.jpg" alt="StudentHub Logo">
            <h2>Practical 9: Secure Registration</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                Duplicate check, Bcrypt password hashing, and MySQLi prepared statement insertion.
            </p>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <span>🎉</span>
                <div>
                    <?php echo $success_msg; ?><br><br>
                    <a href="secure_login.php" class="btn btn-primary btn-sm">Proceed to Secure Login &rarr;</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?php echo htmlspecialchars($errors['general']); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="secure_register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                           value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                           placeholder="Purv Patel" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['full_name']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="student_id">Student ID / Roll No <span class="required">*</span></label>
                    <input type="text" id="student_id" name="student_id" class="form-control"
                           value="<?php echo htmlspecialchars($form_data['student_id']); ?>"
                           placeholder="25CE089" required>
                    <?php if (isset($errors['student_id'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['student_id']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?php echo htmlspecialchars($form_data['username']); ?>"
                           placeholder="purv_patel" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['username']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Institutional Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($form_data['email']); ?>"
                           placeholder="purv@charusat.edu.in" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['email']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number <span class="required">*</span></label>
                    <input type="tel" id="mobile" name="mobile" class="form-control"
                           value="<?php echo htmlspecialchars($form_data['mobile']); ?>"
                           placeholder="9876543210" required>
                    <?php if (isset($errors['mobile'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['mobile']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="gender">Gender <span class="required">*</span></label>
                    <select id="gender" name="gender" class="form-control">
                        <option value="Male" <?php echo $form_data['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $form_data['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $form_data['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" class="form-control">
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Electronics & Communication">Electronics & Communication</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="course">Course / Degree</label>
                    <select id="course" name="course" class="form-control">
                        <option value="B.Tech CE">B.Tech CE</option>
                        <option value="B.Tech IT">B.Tech IT</option>
                        <option value="B.Tech EC">B.Tech EC</option>
                        <option value="B.Tech ME">B.Tech ME</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min 8 chars, 1 uppercase, 1 number" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['password']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                           placeholder="Repeat chosen password" required>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                            ⚠️ <?php echo htmlspecialchars($errors['confirm_password']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    🔒 Register Account with Bcrypt Hashing
                </button>
            </div>
        </form>

        <div class="auth-footer">
            Already registered? <a href="secure_login.php">Sign In to StudentHub &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
