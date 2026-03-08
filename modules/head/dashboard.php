<?php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$head_id = $user['user_id'];

// Get Department ID
$dept_stmt = $mysqli->prepare("SELECT id, name FROM departments WHERE head_id = ?");
$dept_stmt->bind_param("i", $head_id);
$dept_stmt->execute();
$dept_res = $dept_stmt->get_result();
$dept = $dept_res->fetch_assoc();
$dept_stmt->close();

$dept_id = $dept ? $dept['id'] : 0;
$dept_name = $dept ? $dept['name'] : 'Unknown Department';

// At-Risk Students Count (Overall)
$at_risk_count = 0;
if ($dept_id) {
    $risk_stmt = $mysqli->prepare("
        SELECT s.id, 
               SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) * 1.0 / COUNT(a.id) as rate
        FROM students s
        JOIN attendance a ON s.id = a.student_id
        WHERE s.department_id = ?
        GROUP BY s.id
        HAVING rate < 0.75
    ");
    $risk_stmt->bind_param("i", $dept_id);
    $risk_stmt->execute();
    $risk_stmt->store_result();
    $at_risk_count = $risk_stmt->num_rows;
    $risk_stmt->close();
}

// Calculate Stats Per Year (1, 2, 3, 4)
$years_data = [
    1 => ['grade' => 0, 'attendance' => 0, 'att_total' => 0, 'att_present' => 0],
    2 => ['grade' => 0, 'attendance' => 0, 'att_total' => 0, 'att_present' => 0],
    3 => ['grade' => 0, 'attendance' => 0, 'att_total' => 0, 'att_present' => 0],
    4 => ['grade' => 0, 'attendance' => 0, 'att_total' => 0, 'att_present' => 0]
];

if ($dept_id) {
    // 1. Grades per Year
    // Join coursework -> students to get semester
    // Year 1: Sem 1,2 | Year 2: Sem 3,4 | etc.
    $g_sql = "SELECT AVG(cw.grade) as avg_g, CEIL(s.current_semester / 2) as year_level
              FROM coursework cw
              JOIN students s ON cw.student_id = s.id
              WHERE s.department_id = ?
              GROUP BY year_level";
    $stmt = $mysqli->prepare($g_sql);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $y = intval($row['year_level']);
        if (isset($years_data[$y])) {
            $years_data[$y]['grade'] = number_format($row['avg_g'], 1);
        }
    }
    $stmt->close();

    // 2. Attendance per Year
    $a_sql = "SELECT CEIL(s.current_semester / 2) as year_level,
                     COUNT(*) as total,
                     SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present
              FROM attendance a
              JOIN students s ON a.student_id = s.id
              WHERE s.department_id = ?
              GROUP BY year_level";
    $stmt = $mysqli->prepare($a_sql);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $y = intval($row['year_level']);
        if (isset($years_data[$y])) {
            $years_data[$y]['att_total'] = $row['total'];
            $years_data[$y]['att_present'] = $row['present'];
            if ($row['total'] > 0) {
                $years_data[$y]['attendance'] = number_format(($row['present'] / $row['total']) * 100, 1);
            }
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Head Dashboard - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: all 0.3s; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0,0,0,0.15); }
        .stat-val { font-size: 1.8rem; font-weight: bold; }
    </style>
</head>
<body class="bg-light">
    <header class="navbar navbar-dark bg-dark px-3">
        <span class="navbar-brand mb-0 h1">Head of Department: <?php echo htmlspecialchars($dept_name); ?></span>
        <a href="/CourseMonitor/public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </header>
    <main class="container py-4">
        <h2 class="mb-4">Department Overview</h2>
        
        <div class="row g-4 mb-5">
            <!-- Year 1 -->
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-header bg-primary text-white text-center fw-bold">Year 1</div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <small class="text-muted d-block">Avg Grade</small>
                            <span class="stat-val text-primary"><?php echo $years_data[1]['grade']; ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Attendance</small>
                            <span class="stat-val text-success"><?php echo $years_data[1]['attendance']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Year 2 -->
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-header bg-info text-white text-center fw-bold">Year 2</div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <small class="text-muted d-block">Avg Grade</small>
                            <span class="stat-val text-primary"><?php echo $years_data[2]['grade']; ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Attendance</small>
                            <span class="stat-val text-success"><?php echo $years_data[2]['attendance']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year 3 -->
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-header bg-warning text-dark text-center fw-bold">Year 3</div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <small class="text-muted d-block">Avg Grade</small>
                            <span class="stat-val text-primary"><?php echo $years_data[3]['grade']; ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Attendance</small>
                            <span class="stat-val text-success"><?php echo $years_data[3]['attendance']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Year 4 -->
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-header bg-success text-white text-center fw-bold">Year 4</div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <small class="text-muted d-block">Avg Grade</small>
                            <span class="stat-val text-primary"><?php echo $years_data[4]['grade']; ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Attendance</small>
                            <span class="stat-val text-success"><?php echo $years_data[4]['attendance']; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg>
            <div>
                <strong>At-Risk Students (Attendance < 75%):</strong> <span class="fs-5 fw-bold ms-2"><?php echo $at_risk_count; ?></span>
            </div>
        </div>
        
        <h3 class="mb-3">Management</h3>
        <div class="list-group mb-4 shadow-sm">
            <a href="/CourseMonitor/modules/head/reports.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                View Reports
                <span class="badge bg-primary rounded-pill">Details</span>
            </a>
            <a href="/CourseMonitor/modules/head/modify_records.php" class="list-group-item list-group-item-action">Modify Records</a>
            <a href="/CourseMonitor/modules/head/audit_logs.php" class="list-group-item list-group-item-action">View Modification History</a>
            <a href="/CourseMonitor/modules/head/courses.php" class="list-group-item list-group-item-action text-primary">Manage Department Courses</a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>