<?php
// api/get_gradebook.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['lecturer', 'head', 'admin']);

header('Content-Type: application/json');

$course_id = intval($_GET['course_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!$course_id) {
    echo json_encode(['error' => 'Course ID required']);
    exit;
}

// 1. Authorization & Course Details
$stmt = $mysqli->prepare("SELECT department_id, lecturer_id FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$res = $stmt->get_result();
$course = $res->fetch_assoc();
$stmt->close();

if (!$course) {
    echo json_encode(['error' => 'Course not found']);
    exit;
}

if ($role === 'lecturer' && $course['lecturer_id'] != $user_id) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
if ($role === 'head') {
    // Check dept
    $check = $mysqli->prepare("SELECT id FROM departments WHERE id = ? AND head_id = ?");
    $check->bind_param("ii", $course['department_id'], $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $check->close();
}

// 2. Fetch Students (Reusing logic for robust enrollment)
// We need to fetch students first to ensure we list everyone, even those with no grades.
// Implicit logic: Dept Match + Semester Match
// Get Semesters for course
$sem_stmt = $mysqli->prepare("SELECT semester_number FROM course_semesters WHERE course_id = ?");
$sem_stmt->bind_param("i", $course_id);
$sem_stmt->execute();
$sem_res = $sem_stmt->get_result();
$semesters = [];
while ($row = $sem_res->fetch_assoc()) $semesters[] = $row['semester_number'];
$sem_stmt->close();

$dept_id = $course['department_id'];
$sql_students = "SELECT DISTINCT s.id, s.name, s.student_id_number 
                 FROM students s
                 LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = $course_id
                 WHERE (e.id IS NOT NULL)";

if ($dept_id > 0 && !empty($semesters)) {
    $sem_list = implode(',', array_map('intval', $semesters));
    $sql_students .= " OR (s.department_id = $dept_id AND s.current_semester IN ($sem_list))";
}
$sql_students .= " ORDER BY s.name ASC";

$students = [];
$res_s = $mysqli->query($sql_students);
if ($res_s) {
    while ($row = $res_s->fetch_assoc()) {
        $students[] = $row;
    }
}

// 3. Fetch Assessment Types
$assessments = [];
$stmt_a = $mysqli->prepare("SELECT DISTINCT assessment_type FROM coursework WHERE course_id = ? ORDER BY assessment_type");
$stmt_a->bind_param("i", $course_id);
$stmt_a->execute();
$res_a = $stmt_a->get_result();
while ($row = $res_a->fetch_assoc()) {
    $assessments[] = $row['assessment_type'];
}
$stmt_a->close();

// 4. Fetch Grades
$grades = [];
$stmt_g = $mysqli->prepare("SELECT student_id, assessment_type, grade FROM coursework WHERE course_id = ?");
$stmt_g->bind_param("i", $course_id);
$stmt_g->execute();
$res_g = $stmt_g->get_result();
while ($row = $res_g->fetch_assoc()) {
    if (!isset($grades[$row['student_id']])) {
        $grades[$row['student_id']] = [];
    }
    $grades[$row['student_id']][$row['assessment_type']] = $row['grade'];
}
$stmt_g->close();

echo json_encode([
    'students' => $students,
    'assessments' => $assessments,
    'grades' => $grades
]);
?>