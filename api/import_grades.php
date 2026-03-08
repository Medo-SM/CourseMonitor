<?php
// api/import_grades.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('lecturer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$course_id = intval($_POST['course_id']);
$assessment_type = trim($_POST['assessment_type']);
$lecturer_id = $_SESSION['user_id'];

if (!$course_id || !$assessment_type) {
    echo json_encode(['error' => 'Course and Assessment Type are required']);
    exit;
}

// Verify ownership
$stmt = $mysqli->prepare("SELECT id, department_id FROM courses WHERE id = ? AND lecturer_id = ?");
$stmt->bind_param("ii", $course_id, $lecturer_id);
$stmt->execute();
$res = $stmt->get_result();
$course_data = $res->fetch_assoc();
if (!$course_data) {
    echo json_encode(['error' => 'Unauthorized course']);
    exit;
}
$stmt->close();
$course_dept_id = $course_data['department_id'];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'File upload failed']);
    exit;
}

$file = $_FILES['file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === FALSE) {
    echo json_encode(['error' => 'Could not read file']);
    exit;
}

$successCount = 0;
$errors = [];
$rowNum = 0;

// Prepare insert/update statement
$sql = "INSERT INTO coursework (student_id, course_id, assessment_type, grade) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE grade = VALUES(grade)";
$insert = $mysqli->prepare($sql);

while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $rowNum++;
    
    // Determine column mapping
    // Format A (Template): [Number, Name, Grade] -> Grade is index 2
    // Format B (Simple):   [Number, Grade]       -> Grade is index 1
    
    $col_count = count($data);
    $student_id_str = trim($data[0]);
    $grade_val = null;

    // Skip Header Row checks
    if ($rowNum === 1) {
        // If first column is not numeric (likely "Student Number"), skip
        // Note: Student numbers can be alphanumeric, so check if it contains "Student" or "Number"
        if (stripos($student_id_str, 'Student') !== false || stripos($student_id_str, 'Number') !== false) {
            continue;
        }
    }

    if ($col_count >= 3) {
        // Assume Template Format: [ID, Name, Grade]
        // If Grade is empty, skip
        if ($data[2] === '' || $data[2] === null) continue; 
        $grade_val = $data[2];
    } elseif ($col_count == 2) {
        // Assume Simple Format: [ID, Grade]
        $grade_val = $data[1];
    }

    if (empty($student_id_str) || !is_numeric($grade_val)) continue;

    $grade = floatval($grade_val);

    // Find student internal ID and Dept/Semester
    $find = $mysqli->prepare("
        SELECT s.id, s.department_id, s.current_semester 
        FROM students s 
        WHERE s.student_id_number = ?
    ");
    $find->bind_param("s", $student_id_str);
    $find->execute();
    $res = $find->get_result();
    $student = $res->fetch_assoc();
    $find->close();
    
    if ($student) {
        $student_id = $student['id'];
        
        // Validate Enrollment OR Implicit Match
        $isValid = false;

        // Check explicit
        $enroll = $mysqli->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $enroll->bind_param("ii", $student_id, $course_id);
        $enroll->execute();
        if ($enroll->get_result()->num_rows > 0) {
            $isValid = true;
        }
        $enroll->close();

        // Check implicit if not valid yet
        if (!$isValid) {
            // Check if student's dept matches course's dept AND student's semester matches course's semester(s)
            $implicit = $mysqli->prepare("SELECT 1 FROM course_semesters cs WHERE cs.course_id = ? AND cs.semester_number = ?");
            $implicit->bind_param("ii", $course_id, $student['current_semester']);
            $implicit->execute();
            if ($implicit->get_result()->num_rows > 0 && $student['department_id'] == $course_dept_id) {
                $isValid = true;
            }
            $implicit->close();
        }
        
        if ($isValid) {
            // Insert Grade
            $insert->bind_param("iisd", $student_id, $course_id, $assessment_type, $grade);
            if ($insert->execute()) {
                $successCount++;
            } else {
                $errors[] = "Row $rowNum: DB Error for $student_id_str";
            }
        } else {
            $errors[] = "Row $rowNum: Student $student_id_str not eligible for this course";
        }
    } else {
        $errors[] = "Row $rowNum: Student Number $student_id_str not found";
    }
}

fclose($handle);

echo json_encode([
    'success' => true, 
    'count' => $successCount, 
    'errors' => $errors
]);
?>