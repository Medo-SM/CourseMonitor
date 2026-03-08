<?php
// api/get_courses.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin', 'head', 'lecturer']);

header('Content-Type: application/json');

$user = getCurrentUser();
$role = $user['role'];
$user_id = $user['user_id'];

$sql = "SELECT c.id, c.code, c.name, c.credit_hours, c.department_id, c.lecturer_id, 
               d.name as department_name, u.full_name as lecturer_name,
               GROUP_CONCAT(cs.semester_number) as semesters
        FROM courses c
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN users u ON c.lecturer_id = u.id
        LEFT JOIN course_semesters cs ON c.id = cs.course_id
        WHERE 1=1";

$params = [];
$types = "";

if ($role === 'lecturer') {
    $sql .= " AND c.lecturer_id = ?";
    $params[] = $user_id;
    $types .= "i";
} elseif ($role === 'head') {
    // Get Head's Dept
    $dept_stmt = $mysqli->prepare("SELECT id FROM departments WHERE head_id = ?");
    $dept_stmt->bind_param("i", $user_id);
    $dept_stmt->execute();
    $d_res = $dept_stmt->get_result();
    $dept = $d_res->fetch_assoc();
    $dept_id = $dept ? $dept['id'] : 0;
    
    $sql .= " AND c.department_id = ?";
    $params[] = $dept_id;
    $types .= "i";
} elseif ($role === 'admin' && isset($_GET['department_id']) && $_GET['department_id'] !== '') {
    $sql .= " AND c.department_id = ?";
    $params[] = intval($_GET['department_id']);
    $types .= "i";
}

$sql .= " GROUP BY c.id ORDER BY c.code ASC";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $row['semesters'] = $row['semesters'] ? array_map('intval', explode(',', $row['semesters'])) : [];
    $courses[] = $row;
}

echo json_encode($courses);
?>