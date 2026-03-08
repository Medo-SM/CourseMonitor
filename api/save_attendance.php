<?php
// api/save_attendance.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('lecturer');

header('Content-Type: application/json');

$input_json = file_get_contents('php://input');
$input = json_decode($input_json, true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$course_id = intval($input['course_id']);
$date = $input['date'];
$attendance_data = $input['attendance'];
$user_id = $_SESSION['user_id'];

// 1. Verify Lecturer teaches this course
$stmt = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized access to this course']);
    exit;
}
$stmt->close();

// 2. Validate Date
if (strtotime($date) > time()) {
    echo json_encode(['error' => 'Attendance date cannot be in the future']);
    exit;
}

$mysqli->begin_transaction();

try {
    // Prepare statement for insertion/update
    $sql = "INSERT INTO attendance (student_id, course_id, date, status) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_at = CURRENT_TIMESTAMP";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $student_id = 0;
    $status = '';
    
    // Bind parameters. Note: they are bound by reference.
    $stmt->bind_param("iiss", $student_id, $course_id, $date, $status);

    foreach ($attendance_data as $record) {
        $student_id = intval($record['student_id']);
        $status = $record['status'];
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for student $student_id: " . $stmt->error);
        }
    }
    $stmt->close();

    $mysqli->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>