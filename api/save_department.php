<?php
// api/save_department.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('admin');

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

$id = isset($input['id']) && $input['id'] !== '' ? intval($input['id']) : null;
$name = trim($input['name']);
$head_id = isset($input['head_id']) && $input['head_id'] !== '' ? intval($input['head_id']) : null;
$faculty_id = isset($input['faculty_id']) && $input['faculty_id'] !== '' ? intval($input['faculty_id']) : null;

if (!$name) {
    echo json_encode(['error' => 'Department Name is required']);
    exit;
}

if ($id) {
    // Update
    $stmt = $mysqli->prepare("UPDATE departments SET name=?, head_id=?, faculty_id=? WHERE id=?");
    $stmt->bind_param("siii", $name, $head_id, $faculty_id, $id);
} else {
    // Create
    $stmt = $mysqli->prepare("INSERT INTO departments (name, head_id, faculty_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $name, $head_id, $faculty_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    if ($mysqli->errno == 1062) {
        echo json_encode(['error' => 'Department Name already exists']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
$stmt->close();
?>