<?php
// api/get_users.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin', 'head']);

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';

$sql = "SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, u.department_id, u.faculty_id, 
               d.name as department_name, f.name as faculty_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN faculties f ON u.faculty_id = f.id
        WHERE 1=1";

if ($search) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
}

$sql .= " ORDER BY u.role, u.full_name ASC";

$stmt = $mysqli->prepare($sql);

if ($search) {
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>