<?php
// api/get_students.php
require_once __DIR__ . '/../includes/auth_functions.php';
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

$dept_id = intval($course['department_id']);
$semesters = $course['semesters'] ? explode(',', $course['semesters']) : [];

// 3. Build Student Query
// Strategy: 
// A. Explicit Enrollments
// B. Implicit Matches (Same Dept + Matching Semester)

$sql = "SELECT DISTINCT s.id, s.name, s.student_id_number, s.department_id, s.current_semester 
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = $course_id
        WHERE (e.id IS NOT NULL)";

// Add Implicit Logic if we have Dept and Semesters
if ($dept_id > 0 && !empty($semesters)) {
    // Sanitize semesters for IN clause
    $sem_list = implode(',', array_map('intval', $semesters));
    $sql .= " OR (s.department_id = $dept_id AND s.current_semester IN ($sem_list))";
}

$sql .= " ORDER BY s.name ASC";

// Execute
$result = $mysqli->query($sql);
if (!$result) {
    echo json_encode(['error' => 'Query Error: ' . $mysqli->error]);
    exit;
}

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
?>