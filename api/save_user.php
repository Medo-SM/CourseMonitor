<?php
// api/save_user.php
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
$username = trim($input['username']);
$email = trim($input['email']);
$full_name = trim($input['full_name']);
$role = $input['role'];
$department_id = isset($input['department_id']) && $input['department_id'] !== '' ? intval($input['department_id']) : null;
$faculty_id = isset($input['faculty_id']) && $input['faculty_id'] !== '' ? intval($input['faculty_id']) : null;
$password = $input['password'] ?? '';
$is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;

// Validation
if (!$username || !$email || !$full_name || !$role) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Logic: 
// Head -> department_id (Required), faculty_id (Null or inferred? Let's just keep it simple: Head manages Dept)
// Lecturer -> faculty_id (Required), department_id (Null)
// Admin -> Both null usually.

if ($role === 'head' && !$department_id) {
    echo json_encode(['error' => 'Department is required for Heads']);
    exit;
}
if ($role === 'lecturer' && !$faculty_id) {
    echo json_encode(['error' => 'Faculty is required for Lecturers']);
    exit;
}

if ($role === 'lecturer') $department_id = null;
if ($role === 'head') $faculty_id = null; // Or keep it if we want Head to be associated with Faculty too, but prompt said "distinguished by faculty NOT department" for Lecturer specifically.

if ($id) {
    // Update
    if ($password) {
        $sql = "UPDATE users SET username=?, email=?, full_name=?, role=?, department_id=?, faculty_id=?, is_active=?, password=? WHERE id=?";
        $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssiiisi", $username, $email, $full_name, $role, $department_id, $faculty_id, $is_active, $hashed_pwd, $id);
    } else {
        $sql = "UPDATE users SET username=?, email=?, full_name=?, role=?, department_id=?, faculty_id=?, is_active=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssiiis", $username, $email, $full_name, $role, $department_id, $faculty_id, $is_active, $id);
    }
} else {
    // Create
    if (!$password) {
        echo json_encode(['error' => 'Password required for new user']);
        exit;
    }
    $sql = "INSERT INTO users (username, email, full_name, role, department_id, faculty_id, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssiis", $username, $email, $full_name, $role, $department_id, $faculty_id, $hashed_pwd, $is_active);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    if ($mysqli->errno == 1062) {
        echo json_encode(['error' => 'Username or Email already exists']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
$stmt->close();
?>