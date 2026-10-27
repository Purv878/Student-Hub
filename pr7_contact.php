<?php
/**
 * Practical 7: PHP Form Processing with Server-Side Validation & CSV/JSON Storage
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();

$errors = [];
$success_msg = "";
$form_data = [
    'full_name'  => '',
    'email'      => '',
    'mobile'     => '',
    'department' => 'Computer Engineering',
    'subject'    => '',
    'message'    => ''
];

$data_dir = __DIR__ . '/data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0777, true);
}
$csv_file = $data_dir . '/contacts.csv';
$json_file = $data_dir . '/contacts.json';

// Handle POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token (Advanced Extension)
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submitted_token)) {
        $errors['csrf'] = "Security check failed: Invalid or expired CSRF token.";
    }

    // 2. Capture and Sanitize Inputs
    $form_data['full_name']  = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['email']      = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $form_data['mobile']     = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['department'] = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['subject']    = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $form_data['message']    = trim(htmlspecialchars(strip_tags($_POST['message'] ?? '')));

    // 3. Robust Server-Side Validations
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = "Full name is required.";
    } elseif (!preg_match("/^[A-Za-z\s]{3,60}$/", $form_data['full_name'])) {
        $errors['full_name'] = "Full name must be 3-60 characters and contain letters only.";
    }

    if (empty($form_data['email'])) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please provide a valid email format (e.g., student@charusat.edu.in).";
    }

    if (empty($form_data['mobile'])) {
        $errors['mobile'] = "Mobile number is required.";
    } elseif (!preg_match("/^[6-9]\d{9}$/", $form_data['mobile'])) {
        $errors['mobile'] = "Mobile must be a valid 10-digit number starting with 6, 7, 8, or 9.";
    }

    if (empty($form_data['subject'])) {
        $errors['subject'] = "Subject line is required.";
    } elseif (strlen($form_data['subject']) < 4) {
        $errors['subject'] = "Subject must be at least 4 characters long.";
    }

    if (empty($form_data['message'])) {
        $errors['message'] = "Message content is required.";
    } elseif (strlen($form_data['message']) < 15) {
        $errors['message'] = "Message must be at least 15 characters to explain your inquiry clearly.";
    }

    // 4. If No Errors, Safely Store in CSV and JSON
    if (empty($errors)) {
        $timestamp = date("Y-m-d H:i:s");
        $ip_addr   = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Prepare storage record
        $record = [
            'id'         => uniqid('msg_'),
            'timestamp'  => $timestamp,
            'full_name'  => $form_data['full_name'],
            'email'      => $form_data['email'],
            'mobile'     => $form_data['mobile'],
            'department' => $form_data['department'],
            'subject'    => $form_data['subject'],
            'message'    => $form_data['message'],
            'ip'         => $ip_addr
        ];

        // --- A. Store in CSV with File Locking (Safe Concurrency) ---
        $is_new_csv = !file_exists($csv_file);
        $fp = fopen($csv_file, 'a');
        if ($fp && flock($fp, LOCK_EX)) {
            if ($is_new_csv) {
                fputcsv($fp, ['ID', 'Timestamp', 'Full Name', 'Email', 'Mobile', 'Department', 'Subject', 'Message', 'IP Address']);
            }
            fputcsv($fp, [
                $record['id'],
                $record['timestamp'],
                $record['full_name'],
                $record['email'],
                $record['mobile'],
                $record['department'],
                $record['subject'],
                $record['message'],
                $record['ip']
            ]);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        // --- B. Store in JSON with File Locking ---
        $existing_json_records = [];
        if (file_exists($json_file)) {
            $raw_json = file_get_contents($json_file);
            $existing_json_records = json_decode($raw_json, true) ?? [];
        }
        $existing_json_records[] = $record;
        file_put_contents($json_file, json_encode($existing_json_records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        $success_msg = "Thank you, " . htmlspecialchars($form_data['full_name']) . "! Your inquiry has been processed and saved successfully to CSV and JSON storage.";

        // Clear form values on success
        $form_data = [
            'full_name'  => '',
            'email'      => '',
            'mobile'     => '',
            'department' => 'Computer Engineering',
            'subject'    => '',
            'message'    => ''
        ];
    }
}

$csrf_token = get_csrf_token();
$page_title = "Practical 7: Server-Side Form Processing & File Storage";
$active_nav = "contact";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Practical 7: PHP Form Processing & File Storage</h1>
        <p>Server-side POST handling, sanitization, validation, CSRF verification, and atomic CSV/JSON persistence.</p>
    </div>
    <div>
        <a href="pr7_records.php" class="btn btn-secondary">📂 View Stored CSV / JSON Records</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Form Card -->
    <div class="backend-card">
        <div class="backend-card-header">
            <h3>Student Inquiry & Feedback Form (POST Method)</h3>
            <span class="badge badge-info">Server Validation Active</span>
        </div>
        <div class="backend-card-body">
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <div>
                        <strong>Submission Successful!</strong><br>
                        <?php echo $success_msg; ?><br>
                        <a href="pr7_records.php" style="color: inherit; text-decoration: underline; font-weight: 600;">Click here to view record in file storage &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['csrf'])): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <div><?php echo htmlspecialchars($errors['csrf']); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="pr7_contact.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Student Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                               placeholder="e.g. Purv Patel" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                                ⚠️ <?php echo htmlspecialchars($errors['full_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Institutional Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['email']); ?>"
                               placeholder="e.g. 25ce089@charusat.edu.in" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                                ⚠️ <?php echo htmlspecialchars($errors['email']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number (10 Digits) <span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['mobile']); ?>"
                               placeholder="e.g. 9876543210" required>
                        <?php if (isset($errors['mobile'])): ?>
                            <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                                ⚠️ <?php echo htmlspecialchars($errors['mobile']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="department">Department / Stream <span class="required">*</span></label>
                        <select id="department" name="department" class="form-control">
                            <option value="Computer Engineering" <?php echo $form_data['department'] === 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engineering (CE)</option>
                            <option value="Information Technology" <?php echo $form_data['department'] === 'Information Technology' ? 'selected' : ''; ?>>Information Technology (IT)</option>
                            <option value="Electronics & Communication" <?php echo $form_data['department'] === 'Electronics & Communication' ? 'selected' : ''; ?>>Electronics & Communication (EC)</option>
                            <option value="Mechanical Engineering" <?php echo $form_data['department'] === 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering (ME)</option>
                            <option value="Civil Engineering" <?php echo $form_data['department'] === 'Civil Engineering' ? 'selected' : ''; ?>>Civil Engineering (CL)</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="subject">Inquiry Subject / Topic <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['subject']); ?>"
                               placeholder="e.g. Attendance Discrepancy / Event Registration" required>
                        <?php if (isset($errors['subject'])): ?>
                            <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                                ⚠️ <?php echo htmlspecialchars($errors['subject']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full-width">
                        <label for="message">Detailed Inquiry / Message <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="4"
                                  placeholder="Describe your inquiry in detail (minimum 15 characters)..." required><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="form-help" style="color: var(--danger-color); font-weight: 600;">
                                ⚠️ <?php echo htmlspecialchars($errors['message']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        💾 Submit Form (POST Processing)
                    </button>
                    <button type="reset" class="btn btn-secondary">Clear Fields</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Practical 7 Information Card -->
    <div>
        <div class="backend-card">
            <div class="backend-card-header">
                <h3>Practical 7 Highlights</h3>
            </div>
            <div class="backend-card-body" style="font-size: 0.92rem; line-height: 1.7;">
                <p><strong>Methodology:</strong></p>
                <ul style="margin-left: 1.25rem; margin-bottom: 1rem; color: #475569;">
                    <li><strong>HTTP POST:</strong> Prevents query string exposure in browser history and logs.</li>
                    <li><strong>Sanitization:</strong> <code>filter_input()</code> & <code>htmlspecialchars()</code> stop XSS injection.</li>
                    <li><strong>Validation:</strong> RegEx checks for name & phone, <code>FILTER_VALIDATE_EMAIL</code>.</li>
                    <li><strong>Safe Concurrency:</strong> <code>flock(LOCK_EX)</code> prevents race conditions during file append.</li>
                    <li><strong>CSRF Defense:</strong> Cryptographic token generated with <code>random_bytes(32)</code>.</li>
                </ul>

                <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border-color);">

                <div style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <strong>Files Generated:</strong><br>
                    📄 <code>data/contacts.csv</code><br>
                    📦 <code>data/contacts.json</code>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
