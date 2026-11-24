<?php
// modules/admin/dashboard.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Admin Dashboard';

// Fetch system metric counts safely
$totalUsers = 0;
$totalStudents = 0;
$activeCourses = 0;
$totalDepts = 0;
$totalFaculties = 0;
$recentActivities = [];

if (isset($mysqli) && $mysqli instanceof mysqli && !$mysqli->connect_errno) {
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM users")) {
        $totalUsers = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM students")) {
        $totalStudents = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM courses")) {
        $activeCourses = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM departments")) {
        $totalDepts = (int)($res->fetch_assoc()['c'] ?? 0);
    }
    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM faculties")) {
        $totalFaculties = (int)($res->fetch_assoc()['c'] ?? 0);
    }

    // Fetch recent audit activity
    $actSql = "SELECT al.id, al.table_name, al.record_id, al.action, al.modified_at, al.new_value,
                      u.username as modifier_name, u.full_name as modifier_full_name
               FROM audit_logs al 
               LEFT JOIN users u ON al.modified_by = u.id 
               ORDER BY al.modified_at DESC 
               LIMIT 6";
    if ($res = $mysqli->query($actSql)) {
        while ($row = $res->fetch_assoc()) {
            $recentActivities[] = $row;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Page Header Banner -->
    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Admin Dashboard</h1>
            <p class="cm-page-subtitle">Overview of system accounts, academic records, and recent activity</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge cm-badge-neutral font-monospace">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= date('l, M j, Y') ?>
            </span>
            <span class="badge cm-badge-primary">
                <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Administrator
            </span>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Users -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Total Users</span>
                    <span class="cm-stat-icon primary" aria-hidden="true">
                        <i class="bi bi-people"></i>
                    </span>
                </div>
                <div class="cm-stat-val"><?= number_format($totalUsers) ?></div>
                <p class="cm-stat-meta">
                    <i class="bi bi-person-badge text-primary" aria-hidden="true"></i>
                    <span>Teachers, Heads &amp; Admins</span>
                </p>
            </div>
        </div>

        <!-- Students -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Students</span>
                    <span class="cm-stat-icon success" aria-hidden="true">
                        <i class="bi bi-mortarboard"></i>
                    </span>
                </div>
                <div class="cm-stat-val"><?= number_format($totalStudents) ?></div>
                <p class="cm-stat-meta">
                    <i class="bi bi-check2-circle text-success" aria-hidden="true"></i>
                    <span>Enrolled students</span>
                </p>
            </div>
        </div>

        <!-- Courses -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Courses</span>
                    <span class="cm-stat-icon info" aria-hidden="true">
                        <i class="bi bi-book"></i>
                    </span>
                </div>
                <div class="cm-stat-val"><?= number_format($activeCourses) ?></div>
                <p class="cm-stat-meta">
                    <i class="bi bi-journal-bookmark text-info" aria-hidden="true"></i>
                    <span>Current course catalog</span>
                </p>
            </div>
        </div>

        <!-- Departments -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Departments</span>
                    <span class="cm-stat-icon warning" aria-hidden="true">
                        <i class="bi bi-building"></i>
                    </span>
                </div>
                <div class="cm-stat-val"><?= number_format($totalDepts) ?></div>
                <p class="cm-stat-meta">
                    <i class="bi bi-diagram-2 text-warning" aria-hidden="true"></i>
                    <span>Academic departments</span>
                </p>
            </div>
        </div>

        <!-- Faculties -->
        <div class="col-12 col-sm-6 col-xl">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Faculties</span>
                    <span class="cm-stat-icon" aria-hidden="true">
                        <i class="bi bi-diagram-3"></i>
                    </span>
                </div>
                <div class="cm-stat-val"><?= number_format($totalFaculties) ?></div>
                <p class="cm-stat-meta">
                    <i class="bi bi-grid text-secondary" aria-hidden="true"></i>
                    <span>Faculties &amp; divisions</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="mb-4">
        <h2 class="h5 mb-3 fw-semibold text-slate-800">
            <i class="bi bi-grid-fill me-2 text-primary" aria-hidden="true"></i>Manage Modules
        </h2>
        <div class="row g-3">
            <!-- User Management -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon primary" aria-hidden="true">
                                    <i class="bi bi-person-gear"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">Users</h3>
                            </div>
                            <p class="text-secondary small mb-3">
                                Create and manage user accounts, assign roles (Lecturer, Department Head, Admin), and set affiliations.
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/modules/admin/users.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Manage Users
                        </a>
                    </div>
                </div>
            </div>

            <!-- Student Management -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon success" aria-hidden="true">
                                    <i class="bi bi-mortarboard"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">Students</h3>
                            </div>
                            <p class="text-secondary small mb-3">
                                Register new students, update matriculation IDs, manage current semesters, and track department enrollments.
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/modules/admin/students.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Manage Students
                        </a>
                    </div>
                </div>
            </div>

            <!-- Course Management -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon info" aria-hidden="true">
                                    <i class="bi bi-book"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">Courses</h3>
                            </div>
                            <p class="text-secondary small mb-3">
                                Set up courses, assign lecturers, define credit hours, and configure semester offerings.
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/modules/admin/courses.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Manage Courses
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Management -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon warning" aria-hidden="true">
                                    <i class="bi bi-building"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">Departments</h3>
                            </div>
                            <p class="text-secondary small mb-3">
                                Organize academic departments, connect them to faculties, and appoint Department Heads.
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/modules/admin/departments.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Manage Departments
                        </a>
                    </div>
                </div>
            </div>

            <!-- Faculty Management -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon" aria-hidden="true">
                                    <i class="bi bi-diagram-3"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">Faculties</h3>
                            </div>
                            <p class="text-secondary small mb-3">
                                Manage higher-level faculties, colleges, and academic divisions across the institution.
                            </p>
                        </div>
                        <a href="<?= htmlspecialchars(base_url('/modules/admin/faculties.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right me-1" aria-hidden="true"></i>Manage Faculties
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Overview -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="cm-card h-100">
                    <div class="cm-card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="cm-stat-icon primary" aria-hidden="true">
                                    <i class="bi bi-hdd-network"></i>
                                </span>
                                <h3 class="cm-card-title mb-0">System Status</h3>
                            </div>
                            <ul class="list-unstyled small text-secondary mb-3 space-y-1">
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span>Database:</span>
                                    <span class="badge cm-badge-success">Connected</span>
                                </li>
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span>PHP Version:</span>
                                    <span class="font-monospace text-slate-700"><?= PHP_VERSION ?></span>
                                </li>
                                <li class="d-flex justify-content-between py-1">
                                    <span>App Release:</span>
                                    <span class="badge cm-badge-neutral font-monospace">v2.4.0</span>
                                </li>
                            </ul>
                        </div>
                        <span class="text-muted small text-center">CourseMonitor v2.4.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="cm-card">
        <div class="cm-card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary" aria-hidden="true"></i>
                <h2 class="cm-card-title h6 mb-0">Recent Activity</h2>
            </div>
            <span class="text-muted small">Latest system actions and updates</span>
        </div>
        <div class="cm-card-body p-0">
            <?php if (empty($recentActivities)): ?>
                <div class="cm-empty-state py-5">
                    <div class="cm-empty-icon" aria-hidden="true">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="cm-empty-title">No Recent Activity</h3>
                    <p class="cm-empty-desc">
                        No actions or record changes have been recorded in the activity log yet.
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive border-0">
                    <table class="table cm-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 180px;">Time</th>
                                <th scope="col" style="width: 110px;">Action</th>
                                <th scope="col">Item</th>
                                <th scope="col" style="width: 140px;">Record ID</th>
                                <th scope="col">Changed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivities as $act): ?>
                                <tr>
                                    <td class="font-monospace text-secondary small">
                                        <?= htmlspecialchars(date('M j, Y H:i', strtotime($act['modified_at'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $action = strtoupper($act['action'] ?? 'UNKNOWN');
                                        $badgeClass = 'cm-badge-neutral';
                                        if (str_contains($action, 'CREATE') || str_contains($action, 'INSERT')) {
                                            $badgeClass = 'cm-badge-success';
                                        } elseif (str_contains($action, 'UPDATE') || str_contains($action, 'MODIFY')) {
                                            $badgeClass = 'cm-badge-warning';
                                        } elseif (str_contains($action, 'DELETE')) {
                                            $badgeClass = 'cm-badge-danger';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <code class="text-slate-800"><?= htmlspecialchars($act['table_name'] ?? 'general', ENT_QUOTES, 'UTF-8') ?></code>
                                    </td>
                                    <td class="font-monospace">
                                        #<?= htmlspecialchars((string)($act['record_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-person text-muted" aria-hidden="true"></i>
                                            <span class="fw-medium text-slate-800">
                                                <?= htmlspecialchars($act['modifier_full_name'] ?: ($act['modifier_name'] ?: 'System Administrator'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
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

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
