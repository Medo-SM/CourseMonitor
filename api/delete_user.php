<?php
// api/delete_user.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('admin');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$id = intval($input['id']);
$current_user_id = $_SESSION['user_id'];

if ($id === $current_user_id) {
    echo json_encode(['error' => 'You cannot delete your own account.']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    // Check for foreign key constraint failure
    if ($mysqli->errno == 1451) {
        echo json_encode(['error' => 'Cannot delete user: This user has related records (e.g., Audit Logs) and cannot be permanently deleted. Consider deactivating them instead.']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
$stmt->close();
?>