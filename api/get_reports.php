<?php
// api/get_reports.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('head');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get Department ID managed by this Head
$stmt = $mysqli->prepare("SELECT id FROM departments WHERE head_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$dept_id = $row ? $row['id'] : 0;
$stmt->close();

if (!$dept_id) {
    echo json_encode(['error' => 'You do not manage any department.']);
    exit;
}

// Helper function to safely execute queries
function fetch_single_value($mysqli, $sql, $params, $types) {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row;
}

// Build WHERE clause
// We want stats for courses in this department
$where = "c.department_id = ?";
$params = [$dept_id];
$types = "i";

if ($course_id) {
    $where .= " AND c.id = ?";
    $params[] = $course_id;
    $types .= "i";
}

// 1. Average Grade
// Join coursework -> courses
$sql_avg = "SELECT AVG(cw.grade) as val 
            FROM coursework cw 
            JOIN courses c ON cw.course_id = c.id 
            WHERE $where";
$row_avg = fetch_single_value($mysqli, $sql_avg, $params, $types);
$avg_grade = number_format($row_avg['val'] ?? 0, 2);

// 2. Attendance %
// Join attendance -> courses
$sql_att = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present
            FROM attendance a 
            JOIN courses c ON a.course_id = c.id 
            WHERE $where";
$row_att = fetch_single_value($mysqli, $sql_att, $params, $types);
$att_pct = 0;
if ($row_att && $row_att['total'] > 0) {
    $att_pct = ($row_att['present'] / $row_att['total']) * 100;
}
$att_pct_fmt = number_format($att_pct, 1);

// 3. Grade Distribution
$sql_dist = "SELECT cw.grade 
             FROM coursework cw 
             JOIN courses c ON cw.course_id = c.id 
             WHERE $where";
$stmt = $mysqli->prepare($sql_dist);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res_dist = $stmt->get_result();

$dist = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
while ($r = $res_dist->fetch_assoc()) {
    $g = floatval($r['grade']);
    if ($g >= 90) $dist['A']++;
    elseif ($g >= 80) $dist['B']++;
    elseif ($g >= 70) $dist['C']++;
    elseif ($g >= 60) $dist['D']++;
    else $dist['F']++;
}
$stmt->close();

// 4. At-Risk Students (< 75% attendance)
// Group by Student + Course to see specific course risk
// Or per student overall in Dept? Usually course-specific risk is more actionable.
$sql_risk = "SELECT s.name as student_name, c.code as course_code,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id) as att_pct
             FROM students s
             JOIN attendance a ON s.id = a.student_id
             JOIN courses c ON a.course_id = c.id
             WHERE $where
             GROUP BY s.id, c.id
             HAVING att_pct < 75
             ORDER BY att_pct ASC";

$stmt = $mysqli->prepare($sql_risk);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res_risk = $stmt->get_result();
$at_risk = [];
while ($r = $res_risk->fetch_assoc()) {
    $at_risk[] = $r;
}
$stmt->close();

echo json_encode([
    'avg_grade' => $avg_grade,
    'attendance_percent' => $att_pct_fmt,
    'grade_distribution' => $dist,
    'at_risk' => $at_risk
]);
?>