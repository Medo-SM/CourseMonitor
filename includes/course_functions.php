<?php
// includes/course_functions.php
// Centralized domain functions for course, student, and academic operations

require_once __DIR__ . '/../config/db.php';

/**
 * Retrieves the list of eligible students for a given course.
 * Includes explicit enrollments as well as implicit department/semester matches.
 *
 * @param mysqli $mysqli Database connection
 * @param int $course_id Course identifier
 * @return array List of student records (id, name, student_id_number, department_id, current_semester)
 */
function get_enrolled_students($mysqli, $course_id) {
    $course_id = intval($course_id);
    if ($course_id <= 0) {
        return [];
    }

    // 1. Fetch course department and semester configurations
    $stmt = $mysqli->prepare("SELECT department_id, semesters FROM courses WHERE id = ?");
    if (!$stmt) {
        error_log("Failed to prepare course query: " . $mysqli->error);
        return [];
    }
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$course) {
        return [];
    }

    $dept_id = intval($course['department_id'] ?? 0);
    $semesters = !empty($course['semesters']) ? explode(',', $course['semesters']) : [];

    // 2. Build parameterized query
    $sql = "SELECT DISTINCT s.id, s.name, s.student_id_number, s.department_id, s.current_semester 
            FROM students s
            LEFT JOIN enrollments e ON s.id = e.student_id AND e.course_id = ?
            WHERE (e.id IS NOT NULL)";

    $sem_ints = [];
    foreach ($semesters as $sem) {
        $val = intval(trim($sem));
        if ($val > 0) {
            $sem_ints[] = $val;
        }
    }

    if ($dept_id > 0 && !empty($sem_ints)) {
        $in_clause = implode(',', $sem_ints);
        $sql .= " OR (s.department_id = " . intval($dept_id) . " AND s.current_semester IN ($in_clause))";
    }

    $sql .= " ORDER BY s.name ASC";

    $stmt2 = $mysqli->prepare($sql);
    if (!$stmt2) {
        error_log("Failed to prepare enrolled students query: " . $mysqli->error);
        return [];
    }
    $stmt2->bind_param("i", $course_id);
    $stmt2->execute();
    $res = $stmt2->get_result();

    $students = [];
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt2->close();

    return $students;
}
?>
