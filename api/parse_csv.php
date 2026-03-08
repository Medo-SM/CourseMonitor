<?php
// api/parse_csv.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('lecturer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file']) || !isset($_POST['course_id'])) {
    echo json_encode(['error' => 'Missing file or course_id']);
    exit;
}

$course_id = intval($_POST['course_id']);
$user_id = $_SESSION['user_id'];

// Verify Lecturer
$stmt = $mysqli->prepare("SELECT id FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$stmt->close();

// Fetch valid student IDs for this course
$stmt = $mysqli->prepare("
    SELECT s.student_id_number, s.id 
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    WHERE e.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$valid_students = [];
while ($row = $result->fetch_assoc()) {
    $valid_students[$row['student_id_number']] = $row['id'];
}
$stmt->close();

$file = $_FILES['file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === FALSE) {
    echo json_encode(['error' => 'Cannot open file']);
    exit;
}

$parsed_data = [];
$row_count = 0;

while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $row_count++;
    // Assume Header: StudentID, Grade
    if ($row_count == 1) {
        // Check if header row
        if (isset($data[0]) && strtolower(trim($data[0])) == 'student_id') {
            continue;
        }
    }

    if (count($data) < 2) continue; // Skip incomplete rows

    $student_id_str = trim($data[0]);
    $grade = isset($data[1]) ? floatval(trim($data[1])) : null;
    
    $row_status = [
        'student_id_str' => $student_id_str,
        'grade' => $grade,
        'valid' => true,
        'error' => '',
        'db_student_id' => null
    ];

    // Validate Student
    if (!isset($valid_students[$student_id_str])) {
        $row_status['valid'] = false;
        $row_status['error'] .= 'Student not enrolled. ';
    } else {
        $row_status['db_student_id'] = $valid_students[$student_id_str];
    }

    // Validate Grade
    if ($grade === null || $grade < 0 || $grade > 100) {
        $row_status['valid'] = false;
        $row_status['error'] .= 'Invalid grade (0-100).';
    }

    $parsed_data[] = $row_status;
}

fclose($handle);

echo json_encode(['data' => $parsed_data]);
?>