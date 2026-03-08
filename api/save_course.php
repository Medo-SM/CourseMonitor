<?php
// api/save_course.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['head', 'admin']);

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

// Get Department ID
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
} else if ($role === 'admin') {
    $dept_id = isset($input['department_id']) ? intval($input['department_id']) : 0;
}

$id = isset($input['id']) && $input['id'] !== '' ? intval($input['id']) : null;
$code = trim($input['code']);
$name = trim($input['name']);
$credit_hours = intval($input['credit_hours']);
$lecturer_id = isset($input['lecturer_id']) && $input['lecturer_id'] !== '' ? intval($input['lecturer_id']) : null;
$semesters = isset($input['semesters']) ? $input['semesters'] : []; // Array of ints

if (!$code || !$name || $credit_hours <= 0 || !$dept_id) {
    echo json_encode(['error' => 'All fields (Code, Name, Credit Hours > 0) are required']);
    exit;
}

$mysqli->begin_transaction();

try {
    if ($id) {
        // Update
        if ($role === 'head') {
            $check = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND department_id = ?");
            $check->bind_param("ii", $id, $dept_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                throw new Exception('Unauthorized');
            }
            $check->close();
        }
        
        $stmt = $mysqli->prepare("UPDATE courses SET code=?, name=?, credit_hours=?, department_id=?, lecturer_id=? WHERE id=?");
        $stmt->bind_param("ssiiii", $code, $name, $credit_hours, $dept_id, $lecturer_id, $id);
        $stmt->execute();
        $stmt->close();
        $course_id = $id;
    } else {
        // Create
        $stmt = $mysqli->prepare("INSERT INTO courses (code, name, credit_hours, department_id, lecturer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", $code, $name, $credit_hours, $dept_id, $lecturer_id);
                $stmt->execute();
        $course_id = $mysqli->insert_id;
        $stmt->close();
    } 

    // Update Semesters
    $mysqli->query("DELETE FROM course_semesters WHERE course_id = $course_id");
    if (!empty($semesters)) {
        $stmt_sem = $mysqli->prepare("INSERT INTO course_semesters (course_id, semester_number) VALUES (?, ?)");
        foreach ($semesters as $s_num) {
            $s_num = intval($s_num);
            $stmt_sem->bind_param("ii", $course_id, $s_num);
            $stmt_sem->execute();
        }
        $stmt_sem->close();
    }

    $mysqli->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>