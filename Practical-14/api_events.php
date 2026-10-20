<?php
// ==============================================================================
// Practical 13: PHP JSON API Endpoint for Events
// Features Implemented:
// 1. Returns valid JSON responses with Content-Type header
// 2. Centralized response helper with proper HTTP status codes (200, 201, 400, 404, 500)
// 3. Robust input validation before database operations
// 4. Handles GET (List & Search with debounce) and POST (Create, Update, Delete)
// ==============================================================================

// Set JSON header immediately
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Centralized JSON response helper
function send_api_response($status, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'success'   => ($http_code >= 200 && $http_code < 300),
        'status'    => $status,
        'message'   => $message,
        'data'      => $data,
        'code'      => $http_code,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Global Exception & Error catcher for centralized error handling
set_exception_handler(function($e) {
    send_api_response('error', 'Server Exception: ' . $e->getMessage(), null, 500);
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . "/db.php";

if (!$conn) {
    send_api_response('error', 'Database connection not available', null, 500);
}

// Parse request method and input payload (supports both application/json and form-data/x-www-form-urlencoded)
$method = $_SERVER['REQUEST_METHOD'];
$input = [];

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true) ?? [];
} else {
    $input = $_POST;
}

// Merge query parameters for GET requests
if ($method === 'GET') {
    $input = array_merge($input, $_GET);
}

// -----------------------------------------------------------------------------
// 1. GET REQUEST: List all events or Search with debounce or get single event
// -----------------------------------------------------------------------------
if ($method === 'GET') {
    // If specific event ID requested
    if (isset($input['id']) && is_numeric($input['id'])) {
        $id = (int)$input['id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM `events` WHERE `id` = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $event = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($event) {
            send_api_response('success', 'Event retrieved successfully', $event, 200);
        } else {
            send_api_response('fail', 'Event not found', null, 404);
        }
    }

    // If search keyword provided (Intermediate Extension: Debounced Search)
    $search = trim($input['search'] ?? '');
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $stmt = mysqli_prepare($conn, "SELECT * FROM `events` WHERE `title` LIKE ? OR `location` LIKE ? OR `description` LIKE ? ORDER BY `id` ASC");
        mysqli_stmt_bind_param($stmt, "sss", $searchTerm, $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $events = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $events[] = $row;
        }
        mysqli_stmt_close($stmt);

        send_api_response('success', 'Filtered events retrieved successfully', [
            'count'  => count($events),
            'search' => $search,
            'events' => $events
        ], 200);
    }

    // Default: Return all events
    $res = mysqli_query($conn, "SELECT * FROM `events` ORDER BY `id` ASC");
    $events = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $events[] = $row;
    }

    send_api_response('success', 'All events retrieved successfully', [
        'count'  => count($events),
        'events' => $events
    ], 200);
}

// -----------------------------------------------------------------------------
// 2. POST / PUT / DELETE REQUESTS: Asynchronous CRUD Operations
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    $action = trim($input['action'] ?? 'create');

    // ---------------------------------
    // A. CREATE EVENT (POST / Add)
    // ---------------------------------
    if ($action === 'create' || $action === 'add') {
        // Input validation before database operations
        $title       = trim($input['title'] ?? '');
        $day         = trim($input['day'] ?? '');
        $month       = strtoupper(trim($input['month'] ?? ''));
        $location    = trim($input['location'] ?? '');
        $time        = trim($input['time'] ?? '');
        $description = trim($input['description'] ?? '');
        $button_text = trim($input['button_text'] ?? 'Register');
        $button_link = trim($input['button_link'] ?? '#');

        $errors = [];
        if (empty($title))       $errors['title']       = 'Event title is required.';
        if (empty($day))         $errors['day']         = 'Event day is required (e.g. 15).';
        if (empty($month))       $errors['month']       = 'Event month is required (e.g. AUG).';
        if (empty($location))    $errors['location']    = 'Location is required.';
        if (empty($time))        $errors['time']        = 'Event time is required.';
        if (empty($description)) $errors['description'] = 'Event description is required.';

        if (!empty($errors)) {
            send_api_response('fail', 'Validation failed: Please fill all required fields.', ['errors' => $errors], 400);
        }

        // Insert into database using Prepared Statements
        $stmt = mysqli_prepare($conn, "INSERT INTO `events` (`title`, `day`, `month`, `location`, `time`, `description`, `button_text`, `button_link`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            send_api_response('error', 'Database preparation error: ' . mysqli_error($conn), null, 500);
        }

        mysqli_stmt_bind_param($stmt, "ssssssss", $title, $day, $month, $location, $time, $description, $button_text, $button_link);
        if (mysqli_stmt_execute($stmt)) {
            $insertedId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Fetch newly created record
            $newEventQuery = mysqli_query($conn, "SELECT * FROM `events` WHERE `id` = $insertedId");
            $newEvent = mysqli_fetch_assoc($newEventQuery);

            log_audit($conn, 'Event Created', 'Event Management', "Admin added event '{$title}' ({$day} {$month}) [ID: #{$insertedId}]");

            send_api_response('success', 'Event added successfully!', $newEvent, 201);
        } else {
            $err = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            send_api_response('error', 'Failed to insert event: ' . $err, null, 500);
        }
    }

    // ---------------------------------
    // B. UPDATE EVENT (PUT / Edit)
    // ---------------------------------
    if ($action === 'update' || $action === 'edit') {
        $id          = isset($input['id']) ? (int)$input['id'] : 0;
        $title       = trim($input['title'] ?? '');
        $day         = trim($input['day'] ?? '');
        $month       = strtoupper(trim($input['month'] ?? ''));
        $location    = trim($input['location'] ?? '');
        $time        = trim($input['time'] ?? '');
        $description = trim($input['description'] ?? '');
        $button_text = trim($input['button_text'] ?? 'Register');
        $button_link = trim($input['button_link'] ?? '#');

        if ($id <= 0) {
            send_api_response('fail', 'Valid Event ID is required for update.', null, 400);
        }

        $errors = [];
        if (empty($title))       $errors['title']       = 'Event title is required.';
        if (empty($day))         $errors['day']         = 'Event day is required.';
        if (empty($month))       $errors['month']       = 'Event month is required.';
        if (empty($location))    $errors['location']    = 'Location is required.';
        if (empty($time))        $errors['time']        = 'Event time is required.';
        if (empty($description)) $errors['description'] = 'Event description is required.';

        if (!empty($errors)) {
            send_api_response('fail', 'Validation failed: Please fill all required fields.', ['errors' => $errors], 400);
        }

        // Verify existence
        $checkStmt = mysqli_prepare($conn, "SELECT `id` FROM `events` WHERE `id` = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) === 0) {
            mysqli_stmt_close($checkStmt);
            send_api_response('fail', 'Event not found to update.', null, 404);
        }
        mysqli_stmt_close($checkStmt);

        // Update database
        $stmt = mysqli_prepare($conn, "UPDATE `events` SET `title` = ?, `day` = ?, `month` = ?, `location` = ?, `time` = ?, `description` = ?, `button_text` = ?, `button_link` = ? WHERE `id` = ?");
        if (!$stmt) {
            send_api_response('error', 'Database preparation error: ' . mysqli_error($conn), null, 500);
        }

        mysqli_stmt_bind_param($stmt, "ssssssssi", $title, $day, $month, $location, $time, $description, $button_text, $button_link, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            // Fetch updated record
            $upQuery = mysqli_query($conn, "SELECT * FROM `events` WHERE `id` = $id");
            $updatedEvent = mysqli_fetch_assoc($upQuery);

            log_audit($conn, 'Event Updated', 'Event Management', "Admin updated event #{$id} ('{$title}')");

            send_api_response('success', 'Event updated successfully!', $updatedEvent, 200);
        } else {
            $err = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            send_api_response('error', 'Failed to update event: ' . $err, null, 500);
        }
    }

    // ---------------------------------
    // C. DELETE EVENT (Delete)
    // ---------------------------------
    if ($action === 'delete') {
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            send_api_response('fail', 'Valid Event ID is required for deletion.', null, 400);
        }

        // Check if exists
        $checkStmt = mysqli_prepare($conn, "SELECT `title` FROM `events` WHERE `id` = ?");
        mysqli_stmt_bind_param($checkStmt, "i", $id);
        mysqli_stmt_execute($checkStmt);
        $resCheck = mysqli_stmt_get_result($checkStmt);
        $evRow = mysqli_fetch_assoc($resCheck);
        mysqli_stmt_close($checkStmt);

        if (!$evRow) {
            send_api_response('fail', 'Event not found to delete.', null, 404);
        }

        $eventTitle = $evRow['title'];

        // Delete from database
        $stmt = mysqli_prepare($conn, "DELETE FROM `events` WHERE `id` = ?");
        if (!$stmt) {
            send_api_response('error', 'Database preparation error: ' . mysqli_error($conn), null, 500);
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            log_audit($conn, 'Event Deleted', 'Event Management', "Admin deleted event #{$id} ('{$eventTitle}')");

            send_api_response('success', 'Event deleted successfully!', ['id' => $id], 200);
        } else {
            $err = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            send_api_response('error', 'Failed to delete event: ' . $err, null, 500);
        }
    }

    // ---------------------------------
    // D. REGISTER FOR EVENT
    // ---------------------------------
    if ($action === 'register_event') {
        $eventId = isset($input['event_id']) ? (int)$input['event_id'] : 0;
        $studentId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($input['student_id'] ?? 0);
        $studentName = $_SESSION['user_name'] ?? trim($input['student_name'] ?? '');
        $studentEmail = $_SESSION['user_email'] ?? trim($input['student_email'] ?? '');

        if ($eventId <= 0 || empty($studentName) || empty($studentEmail)) {
            send_api_response('fail', 'Please login or provide valid details to register for this event.', null, 400);
        }

        // Fetch event title
        $evQuery = mysqli_query($conn, "SELECT title FROM `events` WHERE id = $eventId");
        $eventData = mysqli_fetch_assoc($evQuery);
        if (!$eventData) {
            send_api_response('fail', 'Event not found.', null, 404);
        }
        $eventTitle = $eventData['title'];

        // Check if already registered
        $dupQuery = mysqli_prepare($conn, "SELECT id FROM `registrations` WHERE event_id = ? AND student_email = ?");
        mysqli_stmt_bind_param($dupQuery, "is", $eventId, $studentEmail);
        mysqli_stmt_execute($dupQuery);
        mysqli_stmt_store_result($dupQuery);
        if (mysqli_stmt_num_rows($dupQuery) > 0) {
            mysqli_stmt_close($dupQuery);
            send_api_response('fail', 'You are already registered for this event!', null, 400);
        }
        mysqli_stmt_close($dupQuery);

        $regStmt = mysqli_prepare($conn, "INSERT INTO `registrations` (`student_id`, `event_id`, `student_name`, `student_email`, `event_title`, `status`) VALUES (?, ?, ?, ?, ?, 'Confirmed')");
        mysqli_stmt_bind_param($regStmt, "iisss", $studentId, $eventId, $studentName, $studentEmail, $eventTitle);
        if (mysqli_stmt_execute($regStmt)) {
            $newRegId = mysqli_insert_id($conn);
            mysqli_stmt_close($regStmt);

            log_audit($conn, 'Event Registration', 'Registrations', "Student '{$studentName}' registered for event '{$eventTitle}' [Reg #{$newRegId}]", $studentId, $studentName, 'student');

            send_api_response('success', "Registration confirmed for {$eventTitle}!", ['registration_id' => $newRegId], 201);
        } else {
            send_api_response('error', 'Registration failed: ' . mysqli_error($conn), null, 500);
        }
    }

    send_api_response('fail', 'Unknown action specified. Use create, update, delete, or register_event.', null, 400);
}

// Method not allowed
send_api_response('fail', 'HTTP Method not supported: ' . $method, null, 405);
?>
