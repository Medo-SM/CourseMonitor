<?php
// api/save_coursework.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('lecturer');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$course_id = intval($input['course_id']);
$assessment_type = $input['assessment_type'];
$grades = $input['grades'];
$user_id = $_SESSION['user_id'];

// Verify Lecturer
$stmt = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$stmt->close();

$mysqli->begin_transaction();

try {
    $sql = "INSERT INTO coursework (student_id, course_id, assessment_type, grade)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE grade = VALUES(grade), recorded_at = CURRENT_TIMESTAMP";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $student_id = 0;
    $grade_val = 0.0;
    
    // Bind parameters
    $stmt->bind_param("iisd", $student_id, $course_id, $assessment_type, $grade_val);

    foreach ($grades as $item) {
        $grade_val = floatval($item['grade']);
        $student_id = intval($item['student_id']);
        
        if ($grade_val < 0 || $grade_val > 100) {
            throw new Exception("Grade must be between 0 and 100");
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }
    $stmt->close();

    $mysqli->commit();

    // Calculate Average
    $avgStmt = $mysqli->prepare("
        SELECT AVG(grade) as class_avg 
        FROM coursework 
        WHERE course_id = ? AND assessment_type = ?
    ");
    $avgStmt->bind_param("is", $course_id, $assessment_type);
    $avgStmt->execute();
    $avgResult = $avgStmt->get_result();
    $avgRow = $avgResult->fetch_assoc();
    $avg = $avgRow['class_avg'];
    $avgStmt->close();

    echo json_encode([
        'success' => true,
        'average' => number_format($avg, 2)
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>