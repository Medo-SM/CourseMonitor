<?php
// api/get_students_manage.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['head', 'admin']);

header('Content-Type: application/json');

$user = getCurrentUser();
$role = $user['role'];
$search = $_GET['search'] ?? '';

$sql = "SELECT s.id, s.student_id_number, s.name, s.email, s.department_id, s.current_semester, d.name as department_name 
        FROM students s 
        LEFT JOIN departments d ON s.department_id = d.id 
        WHERE 1=1";

$params = [];
$types = "";

if ($role === 'head') {
    $stmt = $mysqli->prepare("SELECT id FROM departments WHERE head_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $dept = $res->fetch_assoc();
    $dept_id = $dept ? $dept['id'] : 0;
    
    $sql .= " AND s.department_id = ?";
    $params[] = $dept_id;
    $types .= "i";
}

if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.student_id_number LIKE ? OR s.email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY s.name ASC LIMIT 50";

$stmt = $mysqli->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
?>