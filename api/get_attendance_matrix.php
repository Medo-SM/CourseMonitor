<?php
// api/get_attendance_matrix.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['lecturer', 'head', 'admin']);

header('Content-Type: application/json');

$course_id = intval($_GET['course_id'] ?? 0);
$from_date = $_GET['from_date'] ?? null;
$to_date = $_GET['to_date'] ?? null;
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
    $check = $mysqli->prepare("SELECT id FROM departments WHERE id = ? AND head_id = ?");
    $check->bind_param("ii", $course['department_id'], $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $check->close();
}

// 2. Fetch Students (Explicit + Implicit)
// Reuse logic from get_gradebook/get_students
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

// 3. Fetch Dates (Columns)
$date_sql = "SELECT DISTINCT date FROM attendance WHERE course_id = ?";
$date_params = [$course_id];
$date_types = "i";

if ($from_date) {
    $date_sql .= " AND date >= ?";
    $date_params[] = $from_date;
    $date_types .= "s";
}
if ($to_date) {
    $date_sql .= " AND date <= ?";
    $date_params[] = $to_date;
    $date_types .= "s";
}
$date_sql .= " ORDER BY date ASC";

$stmt_d = $mysqli->prepare($date_sql);
$stmt_d->bind_param($date_types, ...$date_params);
$stmt_d->execute();
$res_d = $stmt_d->get_result();
$dates = [];
while ($row = $res_d->fetch_assoc()) {
    $dates[] = $row['date'];
}
$stmt_d->close();

// 4. Fetch Attendance Records
$att_sql = "SELECT student_id, date, status FROM attendance WHERE course_id = ?";
$att_params = [$course_id];
$att_types = "i";

if ($from_date) {
    $att_sql .= " AND date >= ?";
    $att_params[] = $from_date;
    $att_types .= "s";
}
if ($to_date) {
    $att_sql .= " AND date <= ?";
    $att_params[] = $to_date;
    $att_types .= "s";
}

$attendance = [];
$stmt_a = $mysqli->prepare($att_sql);
$stmt_a->bind_param($att_types, ...$att_params);
$stmt_a->execute();
$res_a = $stmt_a->get_result();
while ($row = $res_a->fetch_assoc()) {
    if (!isset($attendance[$row['student_id']])) {
        $attendance[$row['student_id']] = [];
    }
    $attendance[$row['student_id']][$row['date']] = $row['status'];
}
$stmt_a->close();

echo json_encode([
    'students' => $students,
    'dates' => $dates,
    'attendance' => $attendance
]);
?>