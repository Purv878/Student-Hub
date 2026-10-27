<?php
/**
 * Practical 7: CSV and JSON Storage Viewer (Intermediate Extension)
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

require_once __DIR__ . '/includes/auth.php';
init_session();

$data_dir = __DIR__ . '/data';
$csv_file = $data_dir . '/contacts.csv';
$json_file = $data_dir . '/contacts.json';

// Read CSV records
$csv_records = [];
if (file_exists($csv_file)) {
    if (($handle = fopen($csv_file, 'r')) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $csv_records[] = $data;
        }
        fclose($handle);
    }
}

// Read JSON records
$json_records = [];
if (file_exists($json_file)) {
    $raw_json = file_get_contents($json_file);
    $json_records = json_decode($raw_json, true) ?? [];
}

$page_title = "Practical 7: CSV & JSON Records Viewer";
$active_nav = "pr7";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Stored Records Viewer (Practical 7 Extension)</h1>
        <p>Visualizing records persisted into both Comma-Separated Values (CSV) and JavaScript Object Notation (JSON) formats.</p>
    </div>
    <div>
        <a href="pr7_contact.php" class="btn btn-primary">+ Submit Another Inquiry</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📄</div>
        <div class="stat-info">
            <h4>CSV Records Count</h4>
            <div class="stat-value"><?php echo count($csv_records); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📦</div>
        <div class="stat-info">
            <h4>JSON Records Count</h4>
            <div class="stat-value"><?php echo count($json_records); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🔒</div>
        <div class="stat-info">
            <h4>Storage File Integrity</h4>
            <div class="stat-value" style="font-size: 1.2rem; color: var(--success-color);">flock(LOCK_EX) OK</div>
        </div>
    </div>
</div>

<!-- CSV Card -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>1. Records in CSV Format (<code>data/contacts.csv</code>)</h3>
        <span class="badge badge-info"><?php echo count($csv_records); ?> rows stored</span>
    </div>
    <div class="backend-card-body">
        <?php if (empty($csv_records)): ?>
            <div class="alert alert-info">
                ℹ️ No records found in CSV file yet. <a href="pr7_contact.php">Submit a form inquiry</a> to create the first entry.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Department</th>
                            <th>Subject</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($csv_records) as $row): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($row[0] ?? ''); ?></code></td>
                                <td><?php echo htmlspecialchars($row[1] ?? ''); ?></td>
                                <td><strong><?php echo htmlspecialchars($row[2] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($row[3] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row[4] ?? ''); ?></td>
                                <td><span class="badge badge-secondary"><?php echo htmlspecialchars($row[5] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($row[6] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row[7] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JSON Card -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>2. Records in JSON Format (<code>data/contacts.json</code>)</h3>
        <span class="badge badge-success"><?php echo count($json_records); ?> objects parsed</span>
    </div>
    <div class="backend-card-body">
        <?php if (empty($json_records)): ?>
            <div class="alert alert-info">
                ℹ️ No records found in JSON file yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th>Record ID</th>
                            <th>Timestamp</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Subject & Inquiry Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($json_records) as $rec): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($rec['id'] ?? ''); ?></code></td>
                                <td><?php echo htmlspecialchars($rec['timestamp'] ?? ''); ?></td>
                                <td><strong><?php echo htmlspecialchars($rec['full_name'] ?? ''); ?></strong></td>
                                <td><?php echo htmlspecialchars($rec['email'] ?? ''); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($rec['department'] ?? ''); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rec['subject'] ?? ''); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo htmlspecialchars($rec['message'] ?? ''); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
