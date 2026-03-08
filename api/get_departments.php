<?php
// api/get_departments.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin', 'head']);

header('Content-Type: application/json');

$stmt = $mysqli->prepare("SELECT id, name FROM departments ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();

$depts = [];
while ($row = $result->fetch_assoc()) {
    $depts[] = $row;
}

echo json_encode($depts);
?>