<?php
// api/get_lecturers.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin', 'head']);

header('Content-Type: application/json');

$stmt = $mysqli->prepare("SELECT id, full_name FROM users WHERE role = 'lecturer' AND is_active = 1 ORDER BY full_name ASC");
$stmt->execute();
$result = $stmt->get_result();

$lecturers = [];
while ($row = $result->fetch_assoc()) {
    $lecturers[] = $row;
}

echo json_encode($lecturers);
?>