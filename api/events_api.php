<?php
/**
 * Practical 13: RESTful PHP JSON API for Events Management
 * Subject: ITUE203 - Web Development Frameworks
 * Student ID: 25CE089
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/audit.php';

/**
 * Standardized Centralized JSON Response Helper
 */
function send_json($status_code, $success, $message, $data = null, $meta = []) {
    http_response_code($status_code);
    $response = [
        'status'    => $status_code,
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('c'),
        'data'      => $data
    ];
    if (!empty($meta)) {
        $response['meta'] = $meta;
    }
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$conn = getMysqliConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Parse incoming JSON payload if present
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true) ?? [];

$action = $_GET['action'] ?? $json_data['action'] ?? $_POST['action'] ?? '';

try {
    // ----------------------------------------------------
    // GET: List or Single Item
    // ----------------------------------------------------
    if ($method === 'GET') {
        if ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                send_json(400, false, "Invalid or missing event ID.");
            }
            $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                send_json(404, false, "Event not found with ID {$id}.");
            }
            send_json(200, true, "Event retrieved successfully.", $event);
        }

        // Default: List Events with Search & Category Filter
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $sql = "SELECT e.*, COUNT(r.id) AS enrolled_count
                FROM events e
                LEFT JOIN registrations r ON e.id = r.event_id
                WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "sss";
        }
        if (!empty($category)) {
            $sql .= " AND e.category = ?";
            $params[] = $category;
            $types .= "s";
        }

        $sql .= " GROUP BY e.id ORDER BY e.event_date ASC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        send_json(200, true, "Fetched " . count($events) . " events successfully.", $events, [
            'total'    => count($events),
            'search'   => $search,
            'category' => $category
        ]);
    }

    // ----------------------------------------------------
    // POST / PUT / DELETE Operations
    // ----------------------------------------------------
    if ($method === 'POST') {
        $input = !empty($json_data) ? $json_data : $_POST;

        // --- Action: Create ---
        if ($action === 'create') {
            $title       = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $category    = trim($input['category'] ?? 'Technical');
            $event_date  = trim($input['event_date'] ?? '');
            $event_time  = trim($input['event_time'] ?? '');
            $venue       = trim($input['venue'] ?? '');
            $capacity    = (int)($input['capacity'] ?? 100);
            $status      = trim($input['status'] ?? 'upcoming');

            if (empty($title) || empty($description) || empty($event_date) || empty($venue)) {
                send_json(422, false, "Validation failed: Title, Description, Date, and Venue are required.");
            }

            $stmt = $conn->prepare("
                INSERT INTO events (title, description, category, event_date, event_time, venue, capacity, poster_image, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'poster_web.png', ?, 1)
            ");
            $stmt->bind_param("ssssssis", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $status);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                log_audit($conn, 1, 'API_EVENT_CREATE', 'events', $new_id, "Created event via JSON API: {$title}");
                $stmt->close();

                // Retrieve newly created event
                $get = $conn->query("SELECT * FROM events WHERE id = {$new_id}");
                send_json(201, true, "Event created asynchronously via JSON API.", $get->fetch_assoc());
            } else {
                send_json(500, false, "Failed to insert event: " . $conn->error);
            }
        }

        // --- Action: Update ---
        if ($action === 'update') {
            $id          = (int)($input['id'] ?? 0);
            $title       = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $category    = trim($input['category'] ?? 'Technical');
            $event_date  = trim($input['event_date'] ?? '');
            $event_time  = trim($input['event_time'] ?? '');
            $venue       = trim($input['venue'] ?? '');
            $capacity    = (int)($input['capacity'] ?? 100);
            $status      = trim($input['status'] ?? 'upcoming');

            if ($id <= 0 || empty($title) || empty($event_date)) {
                send_json(422, false, "Validation failed: Valid ID, Title, and Date are required.");
            }

            $stmt = $conn->prepare("
                UPDATE events
                SET title = ?, description = ?, category = ?, event_date = ?, event_time = ?, venue = ?, capacity = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssisi", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $status, $id);

            if ($stmt->execute()) {
                log_audit($conn, 1, 'API_EVENT_UPDATE', 'events', $id, "Updated event via JSON API: {$title}");
                $stmt->close();
                $get = $conn->query("SELECT * FROM events WHERE id = {$id}");
                send_json(200, true, "Event #{$id} updated successfully.", $get->fetch_assoc());
            } else {
                send_json(500, false, "Update query failed: " . $conn->error);
            }
        }

        // --- Action: Delete ---
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                send_json(400, false, "Invalid event ID provided for deletion.");
            }

            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                log_audit($conn, 1, 'API_EVENT_DELETE', 'events', $id, "Deleted event #{$id} via JSON API");
                $stmt->close();
                send_json(200, true, "Event #{$id} deleted asynchronously.");
            } else {
                send_json(500, false, "Delete failed: " . $conn->error);
            }
        }

        // --- Action: Quick Register Student (AJAX without reload) ---
        if ($action === 'register') {
            $event_id = (int)($input['event_id'] ?? 0);
            $student_id = 1; // Default to student record 1 (Purv Patel / 25CE089)

            if ($event_id <= 0) {
                send_json(400, false, "Valid event ID required.");
            }

            $chk = $conn->prepare("SELECT id FROM registrations WHERE event_id = ? AND student_id = ?");
            $chk->bind_param("ii", $event_id, $student_id);
            $chk->execute();
            if ($chk->get_result()->fetch_assoc()) {
                send_json(409, false, "You are already registered for this event.");
            }
            $chk->close();

            $reg = $conn->prepare("INSERT INTO registrations (event_id, student_id, attendance_status, notes) VALUES (?, ?, 'registered', 'Registered via AJAX Fetch API')");
            $reg->bind_param("ii", $event_id, $student_id);
            if ($reg->execute()) {
                send_json(201, true, "Registration completed successfully without page reload!");
            } else {
                send_json(500, false, "Registration failed: " . $conn->error);
            }
        }

        send_json(400, false, "Unsupported action: '{$action}'.");
    }

    send_json(405, false, "HTTP method {$method} not allowed.");

} catch (Exception $e) {
    send_json(500, false, "Internal Server Error: " . $e->getMessage());
}
?>
