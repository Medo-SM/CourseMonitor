<?php
// api/get_coursework.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['lecturer', 'head']);

header('Content-Type: application/json');

$course_id = intval($_GET['course_id'] ?? 0);
$assessment_type = $_GET['assessment_type'] ?? '';

if (!$course_id || !$assessment_type) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Check permissions
if ($_SESSION['role'] === 'lecturer') {
    $stmt = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
    $stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $stmt->close();
}

// Fetch grades
$stmt = $mysqli->prepare("
    SELECT student_id, grade 
    FROM coursework 
    WHERE course_id = ? AND assessment_type = ?
");

if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("is", $course_id, $assessment_type);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[$row['student_id']] = $row['grade'];
    }
    echo json_encode($grades);
} else {
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
?>