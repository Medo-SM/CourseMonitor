<?php
require_once __DIR__ . '/includes/auth_functions.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            header("Location: " . base_url('/modules/admin/dashboard.php'));
            break;
        case 'head':
            header("Location: " . base_url('/modules/head/dashboard.php'));
            break;
        case 'lecturer':
            header("Location: " . base_url('/modules/lecturer/dashboard.php'));
            break;
        default:
            header("Location: " . base_url('/'));
            break;
    }
    exit;
}

$pageTitle = 'Academic Performance & Attendance Management Portal';
$showNavbar = true;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom" aria-labelledby="hero-heading">
    <div class="container px-3 px-lg-4 py-2 py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill cm-badge-neutral border text-xs font-monospace">
                    <i class="bi bi-shield-check text-primary" aria-hidden="true"></i>
                    <span>Official Academic Registry Portal</span>
                </div>
                
                <h1 id="hero-heading" class="display-5 fw-bold text-slate-900 mb-3 lh-sm text-balance">
                    Academic Performance &amp; Attendance Management System
                </h1>
                
                <p class="lead text-secondary mb-4 text-pretty" style="max-width: 620px;">
                    A unified institutional platform for course governance, continuous coursework grading, and daily lecture attendance verification across academic faculties and departments.
                </p>

                <div class="d-flex flex-wrap gap-3 pt-2">
                    <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                        <span>Sign In to Dashboard</span>
                    </a>
                    <a href="<?= htmlspecialchars(base_url('/public/signup.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                        <span>Request Account</span>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4 mt-4 pt-3 border-top text-xs text-muted">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>Role-Based Access Control</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>CSRF Protected</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>Immutable Audit Trail</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Academic Coursework & Attendance Preview Card -->
                <div class="cm-card shadow-sm border">
                    <div class="cm-card-header bg-slate-50 border-bottom d-flex align-items-center justify-content-between py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="cm-brand-mark" style="width: 24px; height: 24px; font-size: 0.75rem;" aria-hidden="true">
                                <i class="bi bi-journal-check"></i>
                            </span>
                            <span class="fw-semibold text-slate-900 text-sm">CS304 · Database Systems</span>
                        </div>
                        <span class="badge cm-badge-success">Semester 1</span>
                    </div>

                    <div class="cm-card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-xs text-uppercase tracking-wider text-muted fw-semibold">Attendance Overview</span>
                            <span class="cm-tabular text-sm fw-bold text-success">94.8% Active</span>
                        </div>

                        <!-- Progress Bar with WCAG Contrast -->
                        <div class="progress mb-4" style="height: 8px;" role="progressbar" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100" aria-label="Course attendance rate">
                            <div class="progress-bar bg-success" style="width: 94.8%"></div>
                        </div>

                        <!-- Coursework Stats -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-3 rounded-2 bg-slate-50 border text-center">
                                    <span class="d-block text-xs text-muted mb-1">Enrolled Students</span>
                                    <span class="cm-tabular fs-5 fw-bold text-slate-900">48</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-2 bg-slate-50 border text-center">
                                    <span class="d-block text-xs text-muted mb-1">Sessions Held</span>
                                    <span class="cm-tabular fs-5 fw-bold text-slate-900">24 / 28</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Course Activity Snippet -->
                        <div class="pt-2 border-top">
                            <div class="d-flex align-items-center justify-content-between text-xs py-1">
                                <span class="text-secondary"><i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i> Midterm Marks Approved</span>
                                <span class="text-muted cm-tabular">Today</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-xs py-1">
                                <span class="text-secondary"><i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i> Lab 06 Attendance Logged</span>
                                <span class="text-muted cm-tabular">Yesterday</span>
                            </div>
                        </div>
                    </div>

                    <div class="cm-card-footer bg-slate-50 border-top d-flex align-items-center justify-content-between text-xs">
                        <span class="text-muted">Faculty of Computing &amp; Informatics</span>
                        <span class="badge cm-badge-neutral font-monospace">Accredited</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Role Highlights Section -->
<section class="py-5" aria-labelledby="roles-heading">
    <div class="container px-3 px-lg-4">
        <div class="text-center mb-5">
            <h2 id="roles-heading" class="cm-page-title mb-2">Designed for Every Academic Stakeholder</h2>
            <p class="text-secondary mx-auto text-pretty" style="max-width: 600px;">
                Clear operational boundaries and specialized toolsets tailored to instructional staff, department leadership, and institutional registrars.
            </p>
        </div>

        <div class="row g-4">
            <!-- Lecturer Feature Card -->
            <div class="col-lg-4 col-md-6">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-success mb-2">
                            <i class="bi bi-person-video3 me-1" aria-hidden="true"></i> Course Instructors
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">Lecture Attendance &amp; Gradebook</h3>
                        <p class="text-secondary text-sm mb-4">
                            Log attendance status rapidly per session and maintain continuous coursework scores with complete assessment breakdown.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Session attendance logging with Present, Absent, and Late indicators</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Continuous assessment scoring for quizzes, midterms, and finals</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Bulk roster grade imports via standard CSV and Excel formats</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Lecturer</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Head Feature Card -->
            <div class="col-lg-4 col-md-6">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-info mb-2">
                            <i class="bi bi-building me-1" aria-hidden="true"></i> Department Leadership
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">Academic Oversight &amp; Approvals</h3>
                        <p class="text-secondary text-sm mb-4">
                            Monitor departmental syllabus delivery, evaluate grade alteration requests, and review timestamped audit trails.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>Comprehensive departmental course and lecturer allocation tracking</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>Review and approval workflow for post-submission grade modifications</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>Exportable semester attendance summaries and audit logs</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Department Head</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Administrator Feature Card -->
            <div class="col-lg-4 col-md-12">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-primary mb-2">
                            <i class="bi bi-shield-lock me-1" aria-hidden="true"></i> System Administration
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">Institutional System Governance</h3>
                        <p class="text-secondary text-sm mb-4">
                            Manage user accounts, department structures, student registries, and system-wide security configurations.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>User account lifecycle provisioning with role-based access control</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>Faculty, department, and student registry organizational cataloging</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>Course catalog management with assigned credit hours and prerequisites</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Administrator</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Institutional Security & Standards Section -->
<section class="py-5 bg-white border-top border-bottom" aria-label="Institutional Standards">
    <div class="container px-3 px-lg-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-2 bg-slate-100 text-primary fs-4" aria-hidden="true">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Role-Based Access Isolation</h4>
                        <p class="text-xs text-secondary mb-0">
                            Strict cryptographic session controls safeguard student data and grade modifications across every organizational tier.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-2 bg-slate-100 text-primary fs-4" aria-hidden="true">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Immutable Audit Traceability</h4>
                        <p class="text-xs text-secondary mb-0">
                            Every assessment alteration, attendance submission, and user administrative action is recorded with microsecond accuracy.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-2 bg-slate-100 text-primary fs-4" aria-hidden="true">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>
                    <div>
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Standardized Spreadsheet Interchange</h4>
                        <p class="text-xs text-secondary mb-0">
                            Import bulk student rosters and export semester attendance summaries smoothly in universally supported CSV and Excel structures.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
