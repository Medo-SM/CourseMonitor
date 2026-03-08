<?php
// api/get_department_details.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin']);

header('Content-Type: application/json');

$stmt = $mysqli->prepare("
    SELECT d.id, d.name, d.head_id, d.faculty_id, u.full_name as head_name, f.name as faculty_name
    FROM departments d 
    LEFT JOIN users u ON d.head_id = u.id 
    LEFT JOIN faculties f ON d.faculty_id = f.id
    ORDER BY d.name ASC
");
$stmt->execute();
$result = $stmt->get_result();

$depts = [];
while ($row = $result->fetch_assoc()) {
    $depts[] = $row;
}

echo json_encode($depts);
?>