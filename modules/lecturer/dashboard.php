<?php
/**
 * CourseMonitor - Teacher Dashboard (My Classes)
 * Student Attendance & Grades Made Simple
 */

require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$user = getCurrentUser();
$pageTitle = 'My Classes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="cm-page-tag">
                <i class="bi bi-person-workspace me-1" aria-hidden="true"></i>Teacher Portal
            </div>
            <h1 class="cm-page-title">My Classes</h1>
            <p class="cm-page-subtitle">Welcome back, <?= htmlspecialchars($user['full_name']) ?> &mdash; Manage your classes, record attendance, and enter coursework marks.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge cm-badge-neutral font-monospace py-2 px-3">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i><?= date('l, M j, Y') ?>
            </span>
        </div>
    </div>

    <!-- Quick Academic Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Assigned Classes</span>
                    <span class="cm-stat-icon primary" aria-hidden="true">
                        <i class="bi bi-journal-bookmark"></i>
                    </span>
                </div>
                <div>
                    <div id="statCoursesCount" class="cm-stat-val tabular-nums" aria-label="Assigned Classes Count">-</div>
                    <p class="cm-stat-meta mb-0">
                        <i class="bi bi-check2-circle text-success" aria-hidden="true"></i> Active teaching schedule
                    </p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Daily Attendance</span>
                    <span class="cm-stat-icon success" aria-hidden="true">
                        <i class="bi bi-calendar-check"></i>
                    </span>
                </div>
                <div>
                    <div class="cm-stat-val text-success">
                        <i class="bi bi-check-circle-fill fs-4 me-2" aria-hidden="true"></i>Ready
                    </div>
                    <p class="cm-stat-meta mb-0">
                        <i class="bi bi-clock me-1" aria-hidden="true"></i>Take attendance in seconds
                    </p>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-4">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Class Gradebook</span>
                    <span class="cm-stat-icon info" aria-hidden="true">
                        <i class="bi bi-mortarboard"></i>
                    </span>
                </div>
                <div>
                    <div class="cm-stat-val text-primary">
                        <i class="bi bi-table fs-4 me-2" aria-hidden="true"></i>Gradebook
                    </div>
                    <p class="cm-stat-meta mb-0">
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Quizzes, midterms &amp; final exams
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- My Classes Section -->
    <div class="cm-card mb-4">
        <div class="cm-card-header flex-wrap gap-3 py-3">
            <div class="d-flex align-items-center gap-2">
                <h2 class="cm-card-title h5 mb-0">
                    <i class="bi bi-collection me-2 text-primary" aria-hidden="true"></i>My Classes
                </h2>
                <span id="courseCounterBadge" class="badge cm-badge-neutral tabular-nums">Loading...</span>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto" style="min-width: 260px; max-width: 360px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="search" 
                           id="courseSearchInput" 
                           class="form-control border-start-0" 
                           placeholder="Search classes by code or title..." 
                           aria-label="Search classes by code or title"
                           aria-describedby="search-addon"
                           oninput="filterCourses()">
                </div>
            </div>
        </div>

        <div class="cm-card-body p-4">
            <!-- Dynamic Classes Grid -->
            <div id="courseContainer" class="row g-4" aria-live="polite">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading classes...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Retrieving your assigned classes...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let allCourses = [];

    document.addEventListener('DOMContentLoaded', loadCourses);

    function loadCourses() {
        const container = document.getElementById('courseContainer');
        const countBadge = document.getElementById('courseCounterBadge');
        const statCount = document.getElementById('statCoursesCount');

        fetch('<?= htmlspecialchars(base_url('/api/get_courses.php'), ENT_QUOTES, 'UTF-8') ?>')
            .then(r => {
                if (!r.ok) throw new Error('Network error: ' + r.status);
                return r.json();
            })
            .then(courses => {
                allCourses = Array.isArray(courses) ? courses : [];
                
                if (statCount) {
                    statCount.textContent = allCourses.length;
                }
                if (countBadge) {
                    countBadge.textContent = `${allCourses.length} ${allCourses.length === 1 ? 'Class' : 'Classes'}`;
                }

                renderCourses(allCourses);
            })
            .catch(err => {
                console.error('Failed to load courses:', err);
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert cm-alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
                            <div>
                                <h3 class="h6 mb-1 text-danger">Unable to load classes</h3>
                                <p class="mb-0 small">An error occurred while fetching your class list. Please refresh the page or check your connection.</p>
                            </div>
                        </div>
                    </div>
                `;
                if (countBadge) countBadge.textContent = 'Error';
                if (statCount) statCount.textContent = '0';
            });
    }

    function renderCourses(courses) {
        const container = document.getElementById('courseContainer');

        if (courses.length === 0) {
            const hasFilter = document.getElementById('courseSearchInput').value.trim().length > 0;
            if (hasFilter) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="cm-empty-state">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </div>
                            <h3 class="cm-empty-title">No matching classes</h3>
                            <p class="cm-empty-desc">No assigned classes match your search criteria. Try clearing the search filter.</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearCourseSearch()">
                                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear Search
                            </button>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="cm-empty-state">
                            <div class="cm-empty-icon" aria-hidden="true">
                                <i class="bi bi-journal-x"></i>
                            </div>
                            <h3 class="cm-empty-title">No Classes Assigned Yet</h3>
                            <p class="cm-empty-desc">You do not have any classes assigned to your schedule yet. Please contact your Department Head to assign classes to your profile.</p>
                        </div>
                    </div>
                `;
            }
            return;
        }

        container.innerHTML = '';
        courses.forEach(c => {
            const cid = Number(c.id);
            const semestersText = c.semesters && c.semesters.length 
                ? `Semester ${c.semesters.join(', ')}` 
                : 'All Semesters';

            const card = document.createElement('div');
            card.className = 'col-md-6 col-xl-4';
            card.innerHTML = `
                <div class="card cm-card h-100 d-flex flex-column">
                    <div class="cm-card-header bg-white border-bottom py-3">
                        <span class="badge cm-badge-primary font-monospace fs-6 px-2 py-1" title="Course Code">
                            ${escapeHtml(c.code)}
                        </span>
                        <span class="badge cm-badge-neutral tabular-nums" title="Credit Hours">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i>${escapeHtml(c.credit_hours)} Credits
                        </span>
                    </div>
                    <div class="cm-card-body d-flex flex-column flex-grow-1">
                        <h3 class="h5 card-title text-dark fw-semibold mb-2" title="${escapeHtml(c.name)}">
                            ${escapeHtml(c.name)}
                        </h3>
                        <div class="d-flex align-items-center text-muted small mb-2">
                            <i class="bi bi-building me-2 text-secondary" aria-hidden="true"></i>
                            <span class="text-truncate">${escapeHtml(c.department_name || 'Academic Department')}</span>
                        </div>
                        <div class="d-flex align-items-center text-muted small mb-3">
                            <i class="bi bi-layers me-2 text-secondary" aria-hidden="true"></i>
                            <span>${escapeHtml(semestersText)}</span>
                        </div>
                        <p class="card-text text-secondary small mb-4 flex-grow-1">
                            Record daily attendance, enter student marks, and view full class standing.
                        </p>

                        <!-- Quick Action Buttons -->
                        <div class="d-grid gap-2 mb-3">
                            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/attendance.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${cid}" 
                               class="btn btn-primary btn-sm w-100 justify-content-center"
                               title="Take attendance for this class">
                                <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>Take Attendance
                            </a>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="<?= htmlspecialchars(base_url('/modules/lecturer/coursework.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${cid}" 
                                       class="btn btn-outline-primary btn-sm w-100 text-truncate justify-content-center"
                                       title="Enter student marks">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Enter Grades
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= htmlspecialchars(base_url('/modules/lecturer/view_grades.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${cid}" 
                                       class="btn btn-outline-secondary btn-sm w-100 text-truncate justify-content-center"
                                       title="View class gradebook">
                                        <i class="bi bi-table me-1" aria-hidden="true"></i>View Gradebook
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Shortcuts Row -->
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top small text-muted">
                            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/view_attendance.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${cid}" 
                               class="text-decoration-none text-secondary d-inline-flex align-items-center"
                               title="View past attendance sessions">
                                <i class="bi bi-clock-history me-1 text-primary" aria-hidden="true"></i>Attendance History
                            </a>
                            <a href="<?= htmlspecialchars(base_url('/modules/lecturer/import_excel.php'), ENT_QUOTES, 'UTF-8') ?>?course_id=${cid}" 
                               class="text-decoration-none text-secondary d-inline-flex align-items-center"
                               title="Import marks from file">
                                <i class="bi bi-file-earmark-arrow-up me-1 text-primary" aria-hidden="true"></i>Import CSV
                            </a>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }

    function filterCourses() {
        const query = document.getElementById('courseSearchInput').value.toLowerCase().trim();
        if (!query) {
            renderCourses(allCourses);
            return;
        }

        const filtered = allCourses.filter(c => {
            const code = (c.code || '').toLowerCase();
            const name = (c.name || '').toLowerCase();
            const dept = (c.department_name || '').toLowerCase();
            return code.includes(query) || name.includes(query) || dept.includes(query);
        });

        renderCourses(filtered);
    }

    function clearCourseSearch() {
        const input = document.getElementById('courseSearchInput');
        if (input) {
            input.value = '';
            renderCourses(allCourses);
            input.focus();
        }
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>