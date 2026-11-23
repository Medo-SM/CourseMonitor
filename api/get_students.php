<?php
// api/get_students.php
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/course_functions.php';
requireRole(['lecturer', 'head', 'admin']);

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['course_id'])) {
    echo json_encode(['error' => 'Course ID required']);
    exit;
}

$course_id = intval($_GET['course_id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// 1. Fetch Course Details (Dept + Implicit Semesters)
$stmt = $mysqli->prepare("
    SELECT c.department_id, c.lecturer_id,
           GROUP_CONCAT(cs.semester_number) as semesters
    FROM courses c
    LEFT JOIN course_semesters cs ON c.id = cs.course_id
    WHERE c.id = ?
    GROUP BY c.id
");
if (!$stmt) {
    echo json_encode(['error' => 'DB Error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();
$course = $res->fetch_assoc();
$stmt->close();

if (!$course) {
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// 2. Authorization Check
if ($role === 'lecturer' && $course['lecturer_id'] != $user_id) {
    echo json_encode(['error' => 'Unauthorized access to this course']);
    exit;
}

$students = get_enrolled_students($mysqli, $course_id);
echo json_encode($students);
?>