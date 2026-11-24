<?php
// modules/head/dashboard.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$head_id = $user['user_id'];

// Get Department ID and Name
$dept_stmt = $mysqli->prepare("SELECT id, name FROM departments WHERE head_id = ?");
$dept_stmt->bind_param("i", $head_id);
$dept_stmt->execute();
$dept_res = $dept_stmt->get_result();
$dept = $dept_res->fetch_assoc();
$dept_stmt->close();

$dept_id = $dept ? (int)$dept['id'] : 0;
$dept_name = $dept ? $dept['name'] : 'Unknown Department';

// Department Course Count
$courses_count = 0;
if ($dept_id) {
    $c_stmt = $mysqli->prepare("SELECT COUNT(*) FROM courses WHERE department_id = ?");
    $c_stmt->bind_param("i", $dept_id);
    $c_stmt->execute();
    $c_stmt->bind_result($courses_count);
    $c_stmt->fetch();
    $c_stmt->close();
}

// Active Department Lecturers (Teachers) Count
$lecturers_count = 0;
if ($dept_id) {
    $l_stmt = $mysqli->prepare("SELECT COUNT(DISTINCT lecturer_id) FROM courses WHERE department_id = ? AND lecturer_id IS NOT NULL");
    $l_stmt->bind_param("i", $dept_id);
    $l_stmt->execute();
    $l_stmt->bind_result($lecturers_count);
    $l_stmt->fetch();
    $l_stmt->close();
}

// Department Enrolled Students Count
$students_count = 0;
if ($dept_id) {
    $s_stmt = $mysqli->prepare("SELECT COUNT(*) FROM students WHERE department_id = ?");
    $s_stmt->bind_param("i", $dept_id);
    $s_stmt->execute();
    $s_stmt->bind_result($students_count);
    $s_stmt->fetch();
    $s_stmt->close();
}

// Attendance Alerts Count (Overall Attendance < 75%)
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

// Calculate Stats Per Academic Year (1, 2, 3, 4)
$years_data = [
    1 => ['grade' => '0.0', 'attendance' => '0.0', 'att_total' => 0, 'att_present' => 0],
    2 => ['grade' => '0.0', 'attendance' => '0.0', 'att_total' => 0, 'att_present' => 0],
    3 => ['grade' => '0.0', 'attendance' => '0.0', 'att_total' => 0, 'att_present' => 0],
    4 => ['grade' => '0.0', 'attendance' => '0.0', 'att_total' => 0, 'att_present' => 0]
];

if ($dept_id) {
    // 1. Grades per Year (Year 1: Sem 1,2 | Year 2: Sem 3,4 | etc.)
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
            $years_data[$y]['grade'] = number_format((float)$row['avg_g'], 1);
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
            $years_data[$y]['att_total'] = (int)$row['total'];
            $years_data[$y]['att_present'] = (int)$row['present'];
            if ($row['total'] > 0) {
                $years_data[$y]['attendance'] = number_format(($row['present'] / $row['total']) * 100, 1);
            }
        }
    }
    $stmt->close();
}

// Recent Audit Activity
$recent_audits = [];
if ($dept_id) {
    $audit_stmt = $mysqli->prepare("
        SELECT al.id, al.table_name, al.record_id, al.action, al.old_value, al.new_value, al.modified_at,
               u.full_name as modifier_name
        FROM audit_logs al
        JOIN users u ON al.modified_by = u.id
        WHERE al.modified_by = ?
        ORDER BY al.modified_at DESC
        LIMIT 5
    ");
    $audit_stmt->bind_param("i", $head_id);
    $audit_stmt->execute();
    $audit_res = $audit_stmt->get_result();
    while ($row = $audit_res->fetch_assoc()) {
        $recent_audits[] = $row;
    }
    $audit_stmt->close();
}

$pageTitle = 'Department Overview';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Page Header -->
    <div class="cm-page-header">
        <div class="cm-page-header-content">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge cm-badge-info">Department Head</span>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small fw-medium"><?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h1 class="cm-page-title">Department Overview</h1>
            <p class="cm-page-subtitle">Monitor course performance, teacher assignments, and student attendance at a glance</p>
        </div>
        <div class="cm-page-actions">
            <a href="<?= htmlspecialchars(base_url('/modules/head/reports.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-earmark-bar-graph me-1" aria-hidden="true"></i>
                <span>Performance Reports</span>
            </a>
            <a href="<?= htmlspecialchars(base_url('/modules/head/modify_records.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                <span>Fix a Record</span>
            </a>
            <a href="<?= htmlspecialchars(base_url('/modules/head/courses.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-book me-1" aria-hidden="true"></i>
                <span>Department Courses</span>
            </a>
        </div>
    </div>

    <?php if (!$dept_id): ?>
        <div class="alert cm-alert-warning mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
            <div>
                <strong>Department Not Assigned</strong>
                <p class="mb-0 mt-1">Your account is not currently assigned to head a department. Please contact an administrator to link your account.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <section aria-labelledby="summary-metrics-heading" class="mb-4">
        <h2 id="summary-metrics-heading" class="visually-hidden">Department Key Numbers</h2>
        <div class="row g-3">
            <!-- Department Courses -->
            <div class="col-sm-6 col-xl-3">
                <div class="cm-stat-card">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Department Courses</span>
                        <div class="cm-stat-icon primary" aria-hidden="true">
                            <i class="bi bi-book"></i>
                        </div>
                    </div>
                    <div class="cm-stat-val tabular-nums"><?= (int)$courses_count ?></div>
                    <p class="cm-stat-meta">
                        <span class="text-secondary">Curriculum offerings</span>
                    </p>
                </div>
            </div>

            <!-- Teachers (Assigned Lecturers) -->
            <div class="col-sm-6 col-xl-3">
                <div class="cm-stat-card">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Teachers</span>
                        <div class="cm-stat-icon success" aria-hidden="true">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    </div>
                    <div class="cm-stat-val tabular-nums"><?= (int)$lecturers_count ?></div>
                    <p class="cm-stat-meta">
                        <span class="text-secondary">Assigned instructors</span>
                    </p>
                </div>
            </div>

            <!-- Students -->
            <div class="col-sm-6 col-xl-3">
                <div class="cm-stat-card">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Students</span>
                        <div class="cm-stat-icon primary" aria-hidden="true">
                            <i class="bi bi-mortarboard"></i>
                        </div>
                    </div>
                    <div class="cm-stat-val tabular-nums"><?= (int)$students_count ?></div>
                    <p class="cm-stat-meta">
                        <span class="text-secondary">Enrolled in department</span>
                    </p>
                </div>
            </div>

            <!-- Attendance Alerts (<75%) -->
            <div class="col-sm-6 col-xl-3">
                <div class="cm-stat-card <?= $at_risk_count > 0 ? 'border-danger-subtle' : '' ?>">
                    <div class="cm-stat-header">
                        <span class="cm-stat-label">Attendance Alerts (&lt;75%)</span>
                        <div class="cm-stat-icon <?= $at_risk_count > 0 ? 'danger' : 'success' ?>" aria-hidden="true">
                            <i class="bi <?= $at_risk_count > 0 ? 'bi-exclamation-triangle' : 'bi-check2-circle' ?>"></i>
                        </div>
                    </div>
                    <div class="cm-stat-val tabular-nums <?= $at_risk_count > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= (int)$at_risk_count ?>
                    </div>
                    <p class="cm-stat-meta">
                        <span class="badge <?= $at_risk_count > 0 ? 'cm-badge-danger' : 'cm-badge-success' ?>">
                            <?= $at_risk_count > 0 ? 'Below 75% attendance' : 'All students on track' ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Student Progress by Year Level -->
    <section aria-labelledby="cohort-breakdown-heading" class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 id="cohort-breakdown-heading" class="h5 mb-0 fw-bold">Student Progress by Year</h2>
                <p class="text-secondary small mb-0">Average grades and attendance across each class year</p>
            </div>
        </div>

        <div class="row g-3">
            <?php 
            $yearLabels = [
                1 => ['title' => 'Year 1', 'badge' => 'cm-badge-primary', 'semesters' => 'Semesters 1 & 2'],
                2 => ['title' => 'Year 2', 'badge' => 'cm-badge-info', 'semesters' => 'Semesters 3 & 4'],
                3 => ['title' => 'Year 3', 'badge' => 'cm-badge-warning', 'semesters' => 'Semesters 5 & 6'],
                4 => ['title' => 'Year 4', 'badge' => 'cm-badge-success', 'semesters' => 'Semesters 7 & 8']
            ];
            foreach ($yearLabels as $year => $meta): 
                $avgGrade = $years_data[$year]['grade'];
                $attRate = $years_data[$year]['attendance'];
            ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="card cm-card h-100">
                        <div class="cm-card-header py-2 px-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($meta['title']) ?></span>
                                <span class="badge <?= $meta['badge'] ?>"><?= htmlspecialchars($meta['semesters']) ?></span>
                            </div>
                        </div>
                        <div class="cm-card-body p-3">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="text-secondary small">Average Grade</span>
                                    <span class="fw-bold tabular-nums fs-5 text-primary"><?= htmlspecialchars($avgGrade) ?></span>
                                </div>
                                <div class="progress" style="height: 6px;" role="progressbar" aria-label="Average grade for <?= htmlspecialchars($meta['title']) ?>" aria-valuenow="<?= (float)$avgGrade ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-primary" style="width: <?= min(100, max(0, (float)$avgGrade)) ?>%;"></div>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                    <span class="text-secondary small">Attendance Rate</span>
                                    <span class="fw-bold tabular-nums fs-5 <?= (float)$attRate >= 75 ? 'text-success' : 'text-danger' ?>">
                                        <?= htmlspecialchars($attRate) ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;" role="progressbar" aria-label="Attendance rate for <?= htmlspecialchars($meta['title']) ?>" aria-valuenow="<?= (float)$attRate ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar <?= (float)$attRate >= 75 ? 'bg-success' : 'bg-danger' ?>" style="width: <?= min(100, max(0, (float)$attRate)) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="cm-card-footer py-2 px-3 text-muted small d-flex justify-content-between">
                            <span>Recorded sessions:</span>
                            <span class="fw-medium tabular-nums text-secondary"><?= (int)$years_data[$year]['att_total'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Main Grid: Recent Activity & Management Shortcuts -->
    <div class="row g-4">
        <!-- Recent Activity (Change History) -->
        <div class="col-lg-8">
            <div class="card cm-card h-100">
                <div class="cm-card-header">
                    <div>
                        <h2 class="cm-card-title h6 mb-0 fw-bold">Recent Changes</h2>
                        <span class="text-secondary small">Latest grade and attendance updates in your department</span>
                    </div>
                    <a href="<?= htmlspecialchars(base_url('/modules/head/audit_logs.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                        <span>Change History</span>
                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="cm-card-body p-0">
                    <?php if (empty($recent_audits)): ?>
                        <div class="cm-empty-state py-5">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3 class="cm-empty-title h6">No Changes Logged Yet</h3>
                            <p class="cm-empty-desc">When a grade or attendance record is adjusted, you will see a complete record of the change right here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive border-0 rounded-0">
                            <table class="table cm-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Time</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Action</th>
                                        <th scope="col">Change (Old &rarr; New)</th>
                                        <th scope="col">Changed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_audits as $item): 
                                        $timestamp = date('M d, Y H:i', strtotime($item['modified_at']));
                                        $badgeClass = 'cm-badge-neutral';
                                        if (stripos($item['action'], 'UPDATE') !== false) {
                                            $badgeClass = 'cm-badge-info';
                                        } elseif (stripos($item['action'], 'CREATE') !== false) {
                                            $badgeClass = 'cm-badge-success';
                                        } elseif (stripos($item['action'], 'DELETE') !== false) {
                                            $badgeClass = 'cm-badge-danger';
                                        }
                                        $catName = ($item['table_name'] === 'attendance') ? 'Attendance' : 'Grade';
                                    ?>
                                        <tr>
                                            <td class="tabular-nums text-secondary small text-nowrap"><?= htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="badge cm-badge-neutral">
                                                    <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="text-secondary small ms-1 tabular-nums">#<?= (int)$item['record_id'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($item['action'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td class="small">
                                                <span class="text-danger text-decoration-line-through tabular-nums"><?= htmlspecialchars($item['old_value'] ?? 'None', ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="text-secondary mx-1">&rarr;</span>
                                                <span class="text-success fw-semibold tabular-nums"><?= htmlspecialchars($item['new_value'] ?? 'None', ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td class="text-secondary small text-nowrap">
                                                <?= htmlspecialchars($item['modifier_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Shortcuts -->
        <div class="col-lg-4">
            <div class="card cm-card h-100">
                <div class="cm-card-header">
                    <h2 class="cm-card-title h6 mb-0 fw-bold">Quick Actions</h2>
                </div>
                <div class="cm-card-body">
                    <div class="d-flex flex-column gap-3">
                        <a href="<?= htmlspecialchars(base_url('/modules/head/courses.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none p-3 rounded border border-light-subtle bg-body-tertiary d-flex align-items-center gap-3 transition-base">
                            <div class="cm-stat-icon primary flex-shrink-0" aria-hidden="true">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-primary">Department Courses</div>
                                <div class="text-secondary small">Assign teachers and manage semester offerings</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                        </a>

                        <a href="<?= htmlspecialchars(base_url('/modules/head/modify_records.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none p-3 rounded border border-light-subtle bg-body-tertiary d-flex align-items-center gap-3 transition-base">
                            <div class="cm-stat-icon warning flex-shrink-0" aria-hidden="true">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-primary">Fix a Record</div>
                                <div class="text-secondary small">Adjust student marks or attendance with note</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                        </a>

                        <a href="<?= htmlspecialchars(base_url('/modules/head/reports.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none p-3 rounded border border-light-subtle bg-body-tertiary d-flex align-items-center gap-3 transition-base">
                            <div class="cm-stat-icon success flex-shrink-0" aria-hidden="true">
                                <i class="bi bi-bar-chart-line"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-primary">Performance Reports</div>
                                <div class="text-secondary small">Grade distributions and attendance trends</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                        </a>

                        <a href="<?= htmlspecialchars(base_url('/modules/head/audit_logs.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none p-3 rounded border border-light-subtle bg-body-tertiary d-flex align-items-center gap-3 transition-base">
                            <div class="cm-stat-icon primary flex-shrink-0" aria-hidden="true">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-primary">Change History</div>
                                <div class="text-secondary small">Complete log of all verified revisions</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
