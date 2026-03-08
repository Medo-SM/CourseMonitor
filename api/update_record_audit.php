<?php
// api/update_record_audit.php
require_once __DIR__ . '/../includes/auth_functions.php';
requireRole('head');

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
$head_id = $user['user_id'];
$type = $input['type']; // 'attendance' or 'coursework'
$id = intval($input['id']);
$new_value = $input['new_value'];
$new_date = $input['new_date'] ?? null;
$reason = trim($input['reason'] ?? '');

if (!$reason) {
    echo json_encode(['error' => 'Modification reason is required']);
    exit;
}

$mysqli->begin_transaction();

try {
    $old_value = '';
    $table_name = '';

    // 1. Get Old Value & Verify Permissions
    // Head can only modify records for courses in their department.
    if ($type === 'attendance') {
        $table_name = 'attendance';
        
        $sql = "SELECT a.status, a.date, c.department_id 
                FROM attendance a 
                JOIN courses c ON a.course_id = c.id 
                JOIN departments d ON c.department_id = d.id
                WHERE a.id = ? AND d.head_id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $id, $head_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        if (!$row) throw new Exception("Record not found or unauthorized");
        
        $old_value = $row['status'];
        $old_date = $row['date'];
        $stmt->close();

        // Update Status AND Date
        $upd = $mysqli->prepare("UPDATE attendance SET status = ?, date = ? WHERE id = ?");
        $date_to_save = $new_date ? $new_date : $old_date;
        $upd->bind_param("ssi", $new_value, $date_to_save, $id);
        $upd->execute();
        $upd->close();
        
        // Append Date change info to old/new value for audit if it changed
        if ($new_date && $new_date !== $old_date) {
            $old_value .= " ($old_date)";
            $new_value .= " ($new_date)";
        }

    } elseif ($type === 'coursework') {
        $table_name = 'coursework';
        
        $sql = "SELECT cw.grade, c.department_id 
                FROM coursework cw 
                JOIN courses c ON cw.course_id = c.id 
                JOIN departments d ON c.department_id = d.id
                WHERE cw.id = ? AND d.head_id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $id, $head_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        if (!$row) throw new Exception("Record not found or unauthorized");
        
        $old_value = $row['grade'];
        $stmt->close();

        // Update
        $upd = $mysqli->prepare("UPDATE coursework SET grade = ? WHERE id = ?");
        $upd->bind_param("di", $new_value, $id); // Assuming grade is decimal/float
        $upd->execute();
        $upd->close();
    } else {
        throw new Exception("Invalid record type");
    }

    // 2. Insert Audit Log
    $action = "UPDATE (Reason: $reason)";
    $audit = $mysqli->prepare("
        INSERT INTO audit_logs (table_name, record_id, action, old_value, new_value, modified_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $audit->bind_param("sissii", $table_name, $id, $action, $old_value, $new_value, $head_id);
    $audit->execute();
    $audit->close();

    $mysqli->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>