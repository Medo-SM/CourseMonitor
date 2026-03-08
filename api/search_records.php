<?php
// api/search_records.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('head');

header('Content-Type: application/json');

$user = getCurrentUser();
$head_id = $user['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$type = $_GET['type'] ?? '';
$filter_value = $_GET['filter_value'] ?? ''; // Date (Attendance) OR Assessment Type (Coursework)

if (!$course_id || !$type) {
    echo json_encode(['error' => 'Course and Type are required']);
    exit;
}

// Verify Course belongs to Head's Department
$check = $mysqli->prepare("
    SELECT c.id 
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    WHERE c.id = ? AND d.head_id = ?
");
$check->bind_param("ii", $course_id, $head_id);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized access to this course']);
    exit;
}
$check->close();

$results = [];

if ($type === 'attendance') {
    $sql = "
        SELECT a.id, s.name as student_name, s.student_id_number, 
               a.date, a.status as value, 'Attendance' as record_type
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.course_id = ?
    ";
    $params = [$course_id];
    $types = "i";
    
    if ($filter_value) {
        $sql .= " AND a.date = ?";
        $params[] = $filter_value;
        $types .= "s";
    }
    
    $sql .= " ORDER BY s.name ASC, a.date DESC";
    
} elseif ($type === 'coursework') {
    $sql = "
        SELECT cw.id, s.name as student_name, s.student_id_number, 
               cw.assessment_type, cw.grade as value, 'Coursework' as record_type
        FROM coursework cw
        JOIN students s ON cw.student_id = s.id
        WHERE cw.course_id = ?
    ";
    $params = [$course_id];
    $types = "i";
    
    if ($filter_value) {
        $sql .= " AND cw.assessment_type = ?";
        $params[] = $filter_value;
        $types .= "s";
    }
    
    $sql .= " ORDER BY s.name ASC, cw.assessment_type ASC";
    
} else {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode($results);
?>