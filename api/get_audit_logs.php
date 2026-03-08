<?php
// api/get_audit_logs.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole(['head', 'admin']);

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch logs where modified_by is the current user (for Head)
// Admin might want to see all, but for now let's focus on the user's own actions or Dept actions.
// "Reason for Modification... where can be accessed from" -> Implies looking up what I just did.

$sql = "
    SELECT al.id, al.table_name, al.record_id, al.action, al.old_value, al.new_value, al.modified_at,
           u.username as modifier_name
    FROM audit_logs al
    JOIN users u ON al.modified_by = u.id
    WHERE al.modified_by = ?
    ORDER BY al.modified_at DESC
    LIMIT 50
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$logs = [];
while ($row = $res->fetch_assoc()) {
    // Enrich with context if possible
    $details = "Record #" . $row['record_id'];
    
    if ($row['table_name'] === 'attendance') {
        $q = $mysqli->query("SELECT s.name as s_name, c.code FROM attendance a 
                             JOIN students s ON a.student_id = s.id 
                             JOIN courses c ON a.course_id = c.id 
                             WHERE a.id = " . intval($row['record_id']));
        if ($d = $q->fetch_assoc()) {
            $details = "Attendance: {$d['s_name']} ({$d['code']})";
        }
    } elseif ($row['table_name'] === 'coursework') {
        $q = $mysqli->query("SELECT s.name as s_name, c.code, cw.assessment_type FROM coursework cw 
                             JOIN students s ON cw.student_id = s.id 
                             JOIN courses c ON cw.course_id = c.id 
                             WHERE cw.id = " . intval($row['record_id']));
        if ($d = $q->fetch_assoc()) {
            $details = "Grade: {$d['s_name']} ({$d['code']} - {$d['assessment_type']})";
        }
    }

    $row['details'] = $details;
    
    // Parse Reason from Action string "UPDATE (Reason: ...)"
    // Or just leave it as is. The user asked where it is saved.
    
    $logs[] = $row;
}

echo json_encode($logs);
?>