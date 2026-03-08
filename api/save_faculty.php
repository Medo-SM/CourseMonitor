<?php
// api/save_faculty.php
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

if (!$name) {
    echo json_encode(['error' => 'Faculty Name is required']);
    exit;
}

if ($id) {
    // Update
    $stmt = $mysqli->prepare("UPDATE faculties SET name=? WHERE id=?");
    $stmt->bind_param("si", $name, $id);
} else {
    // Create
    $stmt = $mysqli->prepare("INSERT INTO faculties (name) VALUES (?)");
    $stmt->bind_param("s", $name);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    if ($mysqli->errno == 1062) {
        echo json_encode(['error' => 'Faculty Name already exists']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
$stmt->close();
?>