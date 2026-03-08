<?php
// api/delete_department.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('admin');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$id = intval($input['id']);

$stmt = $mysqli->prepare("DELETE FROM departments WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
}
$stmt->close();
?>