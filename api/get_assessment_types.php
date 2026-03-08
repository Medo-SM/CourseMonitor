<?php
// api/get_assessment_types.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['head', 'lecturer']);

header('Content-Type: application/json');

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if (!$course_id) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("SELECT DISTINCT assessment_type FROM coursework WHERE course_id = ? ORDER BY assessment_type ASC");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();

$types = [];
while ($row = $res->fetch_assoc()) {
    $types[] = $row['assessment_type'];
}

echo json_encode($types);
?>