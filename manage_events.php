<?php
/**
 * Practical 12: Event Management CRUD with Poster Upload & File Validation
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

$upload_dir = __DIR__ . '/uploads/posters/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    // Security protection: prevent script execution in uploads directory
    file_put_contents(__DIR__ . '/uploads/.htaccess', "Options -ExecCGI\nphp_flag engine off\n<FilesMatch \"\.(php|php5|phtml)$\">\nOrder Deny,Allow\nDeny from all\n</FilesMatch>");
}

// ----------------------------------------------------
// 1. Delete Event Handler
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];

    // Get poster file path to remove it safely
    $p_stmt = $conn->prepare("SELECT poster_image FROM events WHERE id = ?");
    $p_stmt->bind_param("i", $del_id);
    $p_stmt->execute();
    $poster_row = $p_stmt->get_result()->fetch_assoc();
    $p_stmt->close();

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        if (!empty($poster_row['poster_image']) && !str_starts_with($poster_row['poster_image'], 'poster_') && file_exists($upload_dir . $poster_row['poster_image'])) {
            @unlink($upload_dir . $poster_row['poster_image']);
        }
        log_audit($conn, $admin_id, 'EVENT_DELETE', 'events', $del_id, "Deleted event #{$del_id}");
        $success_msg = "Event #{$del_id} and its associated records have been removed.";
    } else {
        $error_msg = "Could not delete event: " . $conn->error;
    }
    $stmt->close();
}

// ----------------------------------------------------
// 2. Add / Edit Event (POST Handling with File Validation)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error_msg = "CSRF verification token expired. Please reload and retry.";
    } else {
        $event_id    = (int)($_POST['event_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? 'Technical');
        $event_date  = trim($_POST['event_date'] ?? '');
        $event_time  = trim($_POST['event_time'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $capacity    = (int)($_POST['capacity'] ?? 100);
        $status      = trim($_POST['status'] ?? 'upcoming');

        // Validation
        if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue)) {
            $error_msg = "All event fields (Title, Description, Date, Time, Venue) are mandatory.";
        } else {
            $poster_filename = $_POST['existing_poster'] ?? 'default_poster.png';

            // Check if a poster file was uploaded
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['poster'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error_msg = "File upload failed with error code " . $file['error'];
                } else {
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if ($file['size'] > $max_size) {
                        $error_msg = "Poster image exceeds maximum allowed size of 2MB (Uploaded: " . round($file['size']/1024/1024, 2) . "MB).";
                    } else {
                        // Check extension
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

                        // Check MIME type via finfo
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

                        if (!in_array($ext, $allowed_exts) || !in_array($mime_type, $allowed_mimes)) {
                            $error_msg = "Invalid file type ({$mime_type}). Only JPG, PNG, and WebP images are allowed.";
                        } else {
                            // Generate cryptographically secure filename
                            $new_filename = 'poster_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                            $destination = $upload_dir . $new_filename;

                            if (move_uploaded_file($file['tmp_name'], $destination)) {
                                $poster_filename = $new_filename;
                            } else {
                                $error_msg = "Failed to save uploaded poster image to server directory.";
                            }
                        }
                    }
                }
            }

            // Save to DB if no validation errors
            if (empty($error_msg)) {
                if ($event_id > 0) {
                    // Update
                    $up_stmt = $conn->prepare("
                        UPDATE events
                        SET title = ?, description = ?, category = ?, event_date = ?, event_time = ?, venue = ?, capacity = ?, poster_image = ?, status = ?
                        WHERE id = ?
                    ");
                    $up_stmt->bind_param("ssssssissi", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $poster_filename, $status, $event_id);

                    if ($up_stmt->execute()) {
                        log_audit($conn, $admin_id, 'EVENT_UPDATE', 'events', $event_id, "Updated event: {$title}");
                        $success_msg = "Event <strong>" . htmlspecialchars($title) . "</strong> updated successfully.";
                    } else {
                        $error_msg = "Database update error: " . $conn->error;
                    }
                    $up_stmt->close();
                } else {
                    // Insert
                    $ins_stmt = $conn->prepare("
                        INSERT INTO events (title, description, category, event_date, event_time, venue, capacity, poster_image, status, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $ins_stmt->bind_param("ssssssissi", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $poster_filename, $status, $admin_id);

                    if ($ins_stmt->execute()) {
                        $new_eid = $conn->insert_id;
                        log_audit($conn, $admin_id, 'EVENT_CREATE', 'events', $new_eid, "Created new event: {$title}");
                        $success_msg = "New event <strong>" . htmlspecialchars($title) . "</strong> created with validated poster upload.";
                    } else {
                        $error_msg = "Database insert error: " . $conn->error;
                    }
                    $ins_stmt->close();
                }
            }
        }
    }
}

// ----------------------------------------------------
// 3. Fetch Events with Category & Status Filters
// ----------------------------------------------------
$filter_cat = trim($_GET['category'] ?? '');
$filter_st = trim($_GET['status'] ?? '');

$sql = "SELECT e.*, u.username AS creator_name, COUNT(r.id) AS total_enrolled
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_cat)) {
    $sql .= " AND e.category = ?";
    $params[] = $filter_cat;
    $types .= "s";
}
if (!empty($filter_st)) {
    $sql .= " AND e.status = ?";
    $params[] = $filter_st;
    $types .= "s";
}

$sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrf_token = get_csrf_token();
$page_title = "Practical 12: Event Management CRUD & Poster Upload";
$active_nav = "events";
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1>Practical 12: Event Management & File Validation</h1>
        <p>Admin CRUD with file MIME/size verification, secure image renaming, and directory protection.</p>
    </div>
    <div>
        <button onclick="openEventModal()" class="btn btn-primary">+ Create New Event</button>
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

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="manage_events.php" style="display: flex; gap: 1rem; width: 100%; flex-wrap: wrap;">
        <select name="category" class="form-control" style="width: auto;">
            <option value="">All Categories</option>
            <option value="Technical" <?php echo $filter_cat === 'Technical' ? 'selected' : ''; ?>>Technical</option>
            <option value="Workshop" <?php echo $filter_cat === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
            <option value="Sports" <?php echo $filter_cat === 'Sports' ? 'selected' : ''; ?>>Sports</option>
            <option value="Cultural" <?php echo $filter_cat === 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
        </select>

        <select name="status" class="form-control" style="width: auto;">
            <option value="">All Statuses</option>
            <option value="upcoming" <?php echo $filter_st === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
            <option value="ongoing" <?php echo $filter_st === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
            <option value="completed" <?php echo $filter_st === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $filter_st === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($filter_cat) || !empty($filter_st)): ?>
            <a href="manage_events.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Events Table -->
<div class="backend-card">
    <div class="backend-card-header">
        <h3>Campus Events Catalogue</h3>
        <span class="badge badge-info"><?php echo count($events_list); ?> Events Listed</span>
    </div>
    <div class="backend-card-body">
        <?php if (empty($events_list)): ?>
            <div class="alert alert-info">No events found matching the selected filters.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="backend-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Event Title & Category</th>
                            <th>Date & Time</th>
                            <th>Venue & Capacity</th>
                            <th>Registrations</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events_list as $ev): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <?php
                                    $poster_path = 'uploads/posters/' . $ev['poster_image'];
                                    $img_src = file_exists(__DIR__ . '/' . $poster_path) ? $poster_path : 'Charusat.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Poster" style="width: 64px; height: 64px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ev['title']); ?></strong><br>
                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($ev['category']); ?></span>
                                    <small style="color: #64748b; display: block; margin-top: 4px;"><?php echo htmlspecialchars(substr($ev['description'], 0, 75)) . '...'; ?></small>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($ev['event_date'])); ?><br>
                                    <small style="color: #64748b;"><?php echo date('h:i A', strtotime($ev['event_time'])); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($ev['venue']); ?><br>
                                    <small style="color: #64748b;">Cap: <?php echo $ev['capacity']; ?> seats</small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $ev['total_enrolled']; ?> / <?php echo $ev['capacity']; ?> enrolled</span>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-secondary';
                                    if ($ev['status'] === 'upcoming') $badge_class = 'badge-success';
                                    elseif ($ev['status'] === 'ongoing') $badge_class = 'badge-warning';
                                    elseif ($ev['status'] === 'completed') $badge_class = 'badge-info';
                                    elseif ($ev['status'] === 'cancelled') $badge_class = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($ev['status']); ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <button onclick='editEvent(<?php echo json_encode($ev); ?>)' class="btn btn-secondary btn-sm" title="Edit Event">✏️</button>
                                        <a href="manage_events.php?action=delete&id=<?php echo $ev['id']; ?>"
                                           onclick="return confirm('Delete event <?php echo htmlspecialchars(addslashes($ev['title'])); ?>?');"
                                           class="btn btn-danger btn-sm" title="Delete Event">🗑️</a>
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

<!-- Modal -->
<div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; border-radius: var(--radius-md); max-width: 650px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: var(--shadow-lg);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 id="modalEventTitle" style="color: var(--primary-color);">Create Campus Event</h3>
            <button onclick="closeEventModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="manage_events.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="event_id" id="modal_event_id" value="0">
            <input type="hidden" name="existing_poster" id="modal_existing_poster" value="default_poster.png">

            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Event Title <span class="required">*</span></label>
                    <input type="text" name="title" id="m_title" class="form-control" required placeholder="e.g. AI & Machine Learning Symposium">
                </div>

                <div class="form-group full-width">
                    <label>Event Description <span class="required">*</span></label>
                    <textarea name="description" id="m_description" class="form-control" rows="3" required placeholder="Outline event objectives, speakers, and schedule..."></textarea>
                </div>

                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category" id="m_category" class="form-control">
                        <option value="Technical">Technical</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status" id="m_status" class="form-control">
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Event Date <span class="required">*</span></label>
                    <input type="date" name="event_date" id="m_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Event Time <span class="required">*</span></label>
                    <input type="time" name="event_time" id="m_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Venue Location <span class="required">*</span></label>
                    <input type="text" name="venue" id="m_venue" class="form-control" required placeholder="Auditorium / Lab">
                </div>

                <div class="form-group">
                    <label>Maximum Capacity <span class="required">*</span></label>
                    <input type="number" name="capacity" id="m_capacity" class="form-control" min="10" max="1000" value="100" required>
                </div>

                <div class="form-group full-width">
                    <label>Event Poster Image (JPG, PNG, WebP &le; 2MB)</label>
                    <input type="file" name="poster" id="m_poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-help">Strict server validation checks MIME type and image integrity.</div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeEventModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalEventSubmitBtn">Save Event</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEventModal() {
    document.getElementById('modalEventTitle').textContent = "Create New Campus Event";
    document.getElementById('modalEventSubmitBtn').textContent = "Save Event";
    document.getElementById('modal_event_id').value = "0";
    document.getElementById('modal_existing_poster').value = "default_poster.png";
    document.getElementById('m_title').value = "";
    document.getElementById('m_description').value = "";
    document.getElementById('m_category').value = "Technical";
    document.getElementById('m_status').value = "upcoming";
    document.getElementById('m_date').value = "2026-11-25";
    document.getElementById('m_time').value = "10:00";
    document.getElementById('m_venue').value = "CSPIT Seminar Hall";
    document.getElementById('m_capacity').value = "100";
    document.getElementById('eventModal').style.display = "flex";
}

function editEvent(ev) {
    document.getElementById('modalEventTitle').textContent = "Edit Event: " + ev.title;
    document.getElementById('modalEventSubmitBtn').textContent = "Update Event";
    document.getElementById('modal_event_id').value = ev.id;
    document.getElementById('modal_existing_poster').value = ev.poster_image;
    document.getElementById('m_title').value = ev.title;
    document.getElementById('m_description').value = ev.description;
    document.getElementById('m_category').value = ev.category;
    document.getElementById('m_status').value = ev.status;
    document.getElementById('m_date').value = ev.event_date;
    document.getElementById('m_time').value = ev.event_time;
    document.getElementById('m_venue').value = ev.venue;
    document.getElementById('m_capacity').value = ev.capacity;
    document.getElementById('eventModal').style.display = "flex";
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = "none";
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
