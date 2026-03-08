<?php
// api/save_student.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['admin', 'head']);

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

$user = getCurrentUser();
$role = $user['role'];

// If Head, get their department
$dept_id = null;
if ($role === 'head') {
    $stmt = $mysqli->prepare("SELECT id FROM departments WHERE head_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $dept = $res->fetch_assoc();
    if ($dept) {
        $dept_id = $dept['id'];
    } else {
        echo json_encode(['error' => 'You are not assigned to a department']);
        exit;
    }
    $stmt->close();
}

$id = isset($input['id']) && $input['id'] !== '' ? intval($input['id']) : null;
$student_id_number = trim($input['student_id_number']);
$name = trim($input['name']);
$email = trim($input['email']);
$current_semester = isset($input['current_semester']) ? intval($input['current_semester']) : 1;
$input_dept_id = isset($input['department_id']) ? intval($input['department_id']) : 0;

if ($role === 'admin') {
    $dept_id = $input_dept_id;
}

if (!$student_id_number || !$name || !$email || !$dept_id) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if ($id) {
    // Update
    // Check if updating another department's student (if head)
    if ($role === 'head') {
        $check = $mysqli->prepare("SELECT id FROM students WHERE id = ? AND department_id = ?");
        $check->bind_param("ii", $id, $dept_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $check->close();
    }

    $stmt = $mysqli->prepare("UPDATE students SET student_id_number=?, name=?, email=?, department_id=?, current_semester=? WHERE id=?");
    $stmt->bind_param("sssiii", $student_id_number, $name, $email, $dept_id, $current_semester, $id);
} else {
    // Create
    $stmt = $mysqli->prepare("INSERT INTO students (student_id_number, name, email, department_id, current_semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $student_id_number, $name, $email, $dept_id, $current_semester);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    if ($mysqli->errno == 1062) {
        echo json_encode(['error' => 'Student ID or Email already exists']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    }
}
$stmt->close();
?>