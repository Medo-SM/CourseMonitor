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

$pageTitle = 'Student Attendance & Grades Made Simple';
$showNavbar = true;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom" aria-labelledby="hero-heading">
    <div class="container px-3 px-lg-4 py-3 py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill cm-badge-neutral border text-xs">
                    <i class="bi bi-mortarboard text-primary" aria-hidden="true"></i>
                    <span>CourseMonitor Academic Portal</span>
                </div>
                
                <h1 id="hero-heading" class="display-5 fw-bold text-slate-900 mb-3 lh-sm text-balance">
                    CourseMonitor — Student Attendance &amp; Grades Made Simple
                </h1>
                
                <p class="lead text-secondary mb-4 text-pretty" style="max-width: 620px;">
                    A calm, reliable space for teachers, department heads, and administrators to track attendance, enter grades, and support students.
                </p>

                <div class="d-flex flex-wrap align-items-center gap-3 pt-2">
                    <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="<?= htmlspecialchars(base_url('/public/signup.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
                        <span>Create Account</span>
                    </a>
                    <a href="#features" class="btn btn-link text-decoration-none text-secondary d-inline-flex align-items-center gap-1 fs-6 px-2">
                        <span>Explore Features</span>
                        <i class="bi bi-arrow-down-short fs-5" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-4 mt-4 pt-3 border-top text-xs text-muted">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>Role-based access</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>Private &amp; secure</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
                        <span>Easy record exports</span>
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
                        <span class="badge cm-badge-success">Active Semester</span>
                    </div>

                    <div class="cm-card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
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
                                <span class="text-secondary"><i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i> Midterm marks saved</span>
                                <span class="text-muted cm-tabular">Today</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between text-xs py-1">
                                <span class="text-secondary"><i class="bi bi-clock-history text-primary me-1" aria-hidden="true"></i> Class 24 attendance recorded</span>
                                <span class="text-muted cm-tabular">Yesterday</span>
                            </div>
                        </div>
                    </div>

                    <div class="cm-card-footer bg-slate-50 border-top d-flex align-items-center justify-content-between text-xs">
                        <span class="text-muted">Faculty of Computing &amp; Informatics</span>
                        <span class="badge cm-badge-neutral font-monospace">Fall Term</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Role Highlights Section -->
<section id="features" class="py-5" aria-labelledby="roles-heading">
    <div class="container px-3 px-lg-4">
        <div class="text-center mb-5">
            <h2 id="roles-heading" class="cm-page-title mb-2">Built for Your Everyday Work</h2>
            <p class="text-secondary mx-auto text-pretty" style="max-width: 600px;">
                Simple, dedicated tools designed for teachers, department chairs, and administrators.
            </p>
        </div>

        <div class="row g-4">
            <!-- Lecturer / Teacher Feature Card -->
            <div class="col-lg-4 col-md-6">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-success mb-2">
                            <i class="bi bi-person-video3 me-1" aria-hidden="true"></i> Teachers
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">For Teachers</h3>
                        <p class="text-secondary text-sm mb-4">
                            Take attendance in seconds, enter quiz and exam marks, and track student progress at a glance.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Mark students Present, Absent, or Late in one click</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Enter marks for quizzes, assignments, and exams</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-success mt-1" aria-hidden="true"></i>
                            <span>Import class grades quickly from Excel or CSV files</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Teacher</span>
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Department Head Feature Card -->
            <div class="col-lg-4 col-md-6">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-info mb-2">
                            <i class="bi bi-building me-1" aria-hidden="true"></i> Department Heads
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">For Department Heads</h3>
                        <p class="text-secondary text-sm mb-4">
                            Oversee department courses, review grade adjustments, and spot struggling students early.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>See course attendance rates and grade distributions</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>Review and approve grade adjustments with clear notes</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-info mt-1" aria-hidden="true"></i>
                            <span>Export department-wide summaries anytime</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Department Head</span>
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Administrator Feature Card -->
            <div class="col-lg-4 col-md-12">
                <div class="cm-card h-100 p-4 border bg-white shadow-sm d-flex flex-column">
                    <div class="mb-3">
                        <span class="badge cm-badge-primary mb-2">
                            <i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Administrators
                        </span>
                        <h3 class="h5 fw-bold text-slate-900 mt-2 mb-2">For Administrators</h3>
                        <p class="text-secondary text-sm mb-4">
                            Manage user accounts, assign teachers to courses, and configure department settings.
                        </p>
                    </div>

                    <ul class="list-unstyled text-sm text-secondary mb-4 space-y-2 flex-grow-1">
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>Create and manage teacher and department head accounts</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>Organize faculties, departments, and course offerings</span>
                        </li>
                        <li class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-check2 text-primary mt-1" aria-hidden="true"></i>
                            <span>Assign instructors to course sections easily</span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-auto">
                        <a href="<?= htmlspecialchars(base_url('/public/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary w-100">
                            <span>Sign In as Administrator</span>
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reassurance & Highlights Section -->
<section class="py-5 bg-white border-top border-bottom" aria-label="Key Benefits">
    <div class="container px-3 px-lg-4">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="p-2 rounded-2 bg-slate-100 text-primary fs-4" aria-hidden="true">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Protected &amp; Private</h4>
                        <p class="text-xs text-secondary mb-0">
                            Only authorized staff can see student information and grades, keeping personal data safe.
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
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Clear Change History</h4>
                        <p class="text-xs text-secondary mb-0">
                            Every grade and attendance update is recorded with who made it and when, so records stay reliable.
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
                        <h4 class="h6 fw-bold text-slate-900 mb-1">Spreadsheet Friendly</h4>
                        <p class="text-xs text-secondary mb-0">
                            Easily upload student rosters and download reports in Excel and CSV formats without manual copying.
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
