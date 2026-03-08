<?php
// api/download_grade_template.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('lecturer');

$course_id = intval($_GET['course_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    die("Course ID required");
}

// 1. Verify Course & Lecturer
$stmt = $mysqli->prepare("
    SELECT c.id, c.code, c.name, c.department_id,
           GROUP_CONCAT(cs.semester_number) as semesters
    FROM courses c
    LEFT JOIN course_semesters cs ON c.id = cs.course_id
    WHERE c.id = ? AND c.lecturer_id = ?
    GROUP BY c.id
");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$course = $res->fetch_assoc();
$stmt->close();

if (!$course) {
    die("Unauthorized or Course not found");
}

$dept_id = intval($course['department_id']);
$semesters = $course['semesters'] ? explode(',', $course['semesters']) : [];

// 2. Fetch Students (Same robust logic as get_students.php)
$sql = "SELECT DISTINCT s.student_id_number, s.name
        FROM students s
        LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = $course_id
        WHERE (e.id IS NOT NULL)";

if ($dept_id > 0 && !empty($semesters)) {
    $sem_list = implode(',', array_map('intval', $semesters));
    $sql .= " OR (s.department_id = $dept_id AND s.current_semester IN ($sem_list))";
}

$sql .= " ORDER BY s.name ASC";

$result = $mysqli->query($sql);

// 3. Generate CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Grades_Template_' . $course['code'] . '.csv"');

$output = fopen('php://output', 'w');

// Header Row
fputcsv($output, ['Student Number', 'Student Name', 'Grade']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['student_id_number'],
        $row['name'],
        '' // Empty Grade column
    ]);
}

fclose($output);
exit;
?>