<?php
// modules/head/courses.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$head_id = $user['user_id'];

// Get Department Info
$dept_stmt = $mysqli->prepare("SELECT id, name FROM departments WHERE head_id = ?");
$dept_stmt->bind_param("i", $head_id);
$dept_stmt->execute();
$dept_res = $dept_stmt->get_result();
$dept = $dept_res->fetch_assoc();
$dept_stmt->close();

$dept_id = $dept ? (int)$dept['id'] : 0;
$dept_name = $dept ? $dept['name'] : 'Department';

$pageTitle = 'Manage Department Courses';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Page Header -->
    <div class="cm-page-header">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?= htmlspecialchars(base_url('/modules/head/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Dashboard
                </a>
                <span class="text-secondary small">&bull;</span>
                <span class="text-secondary small fw-medium"><?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h1 class="cm-page-title">Department Courses</h1>
            <p class="cm-page-subtitle">Manage curriculum offerings, lecturer distribution, and semester allocations</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openAddModal()" class="btn btn-primary btn-sm" aria-haspopup="dialog">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add New Course</span>
            </button>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Total Courses</span>
                    <div class="cm-stat-icon primary" aria-hidden="true"><i class="bi bi-book"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums" id="statTotalCourses">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Department catalog</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Assigned</span>
                    <div class="cm-stat-icon success" aria-hidden="true"><i class="bi bi-person-check"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums text-success" id="statAssignedCourses">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Assigned to lecturers</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Unassigned</span>
                    <div class="cm-stat-icon warning" aria-hidden="true"><i class="bi bi-person-x"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums text-warning" id="statUnassignedCourses">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Need instructor allocation</span></p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="cm-stat-card">
                <div class="cm-stat-header">
                    <span class="cm-stat-label">Semesters Active</span>
                    <div class="cm-stat-icon info" aria-hidden="true"><i class="bi bi-calendar3"></i></div>
                </div>
                <div class="cm-stat-val tabular-nums" id="statActiveSemesters">-</div>
                <p class="cm-stat-meta"><span class="text-secondary">Semesters covered (1-8)</span></p>
            </div>
        </div>
    </div>

    <!-- Card with Controls & Table -->
    <div class="card cm-card">
        <div class="cm-card-header flex-column flex-md-row align-items-stretch align-items-md-center gap-3">
            <!-- Search & Filters -->
            <div class="d-flex flex-grow-1 flex-wrap gap-2">
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text bg-light border-end-0 text-muted" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="search" id="searchInput" class="form-control border-start-0" 
                           placeholder="Search course code or name..." 
                           aria-label="Search courses" 
                           aria-describedby="search-addon"
                           oninput="filterCourses()">
                </div>

                <div style="min-width: 170px;">
                    <label for="semesterFilter" class="visually-hidden">Filter by Semester</label>
                    <select id="semesterFilter" class="form-select" onchange="filterCourses()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div style="min-width: 170px;">
                    <label for="lecturerFilter" class="visually-hidden">Filter by Lecturer</label>
                    <select id="lecturerFilter" class="form-select" onchange="filterCourses()">
                        <option value="">All Lecturers</option>
                        <option value="assigned">Assigned Only</option>
                        <option value="unassigned">Unassigned Only</option>
                    </select>
                </div>
            </div>

            <!-- Result Count Badge -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small" id="courseCountLabel">Showing all courses</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0 rounded-0">
                <table class="table cm-table table-hover align-middle mb-0" id="coursesTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 120px;">Code</th>
                            <th scope="col">Course Title</th>
                            <th scope="col" class="text-center" style="width: 110px;">Credits</th>
                            <th scope="col">Assigned Lecturer</th>
                            <th scope="col">Offered In</th>
                            <th scope="col" class="text-end" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="courseTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading courses...</span>
                                </div>
                                <span class="text-secondary small">Loading department courses...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State Container -->
            <div id="emptyState" class="cm-empty-state d-none">
                <div class="cm-empty-icon" aria-hidden="true">
                    <i class="bi bi-book"></i>
                </div>
                <h2 class="cm-empty-title h5" id="emptyTitle">No Courses Found</h2>
                <p class="cm-empty-desc" id="emptyDesc">No courses in this department match your active search or filter criteria.</p>
                <button type="button" onclick="resetFilters()" class="btn btn-outline-secondary btn-sm" id="resetFiltersBtn">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="modalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-xl);">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="cm-stat-icon primary" style="width: 32px; height: 32px; font-size: 1rem;" aria-hidden="true">
                        <i class="bi bi-journal-code"></i>
                    </div>
                    <h2 class="modal-title h5 mb-0" id="modalTitle">Add Course</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            
            <form id="courseForm" onsubmit="saveCourse(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="courseId">

                    <!-- Form Alert -->
                    <div id="modalAlert" class="alert cm-alert-danger d-none" role="alert">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <span id="modalAlertMsg"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">
                                Course Code <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="code" class="form-control text-uppercase tabular-nums font-monospace" 
                                   placeholder="e.g. CS201" required maxlength="20" autocomplete="off">
                            <div class="form-text">Unique departmental code.</div>
                        </div>

                        <div class="col-md-8">
                            <label for="name" class="form-label">
                                Course Name <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="name" class="form-control" 
                                   placeholder="e.g. Data Structures &amp; Algorithms" required maxlength="100">
                            <div class="form-text">Full official academic course title.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="credit_hours" class="form-label">
                                Credit Hours <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input type="number" id="credit_hours" class="form-control tabular-nums" 
                                   min="1" max="12" value="3" required>
                            <div class="form-text">Standard credits (1 to 12).</div>
                        </div>

                        <div class="col-md-8">
                            <label for="lecturer_id" class="form-label">Assign Lecturer</label>
                            <select id="lecturer_id" class="form-select">
                                <option value="">Select Lecturer (Optional)</option>
                            </select>
                            <div class="form-text">Responsible faculty instructor.</div>
                        </div>

                        <div class="col-12 mt-4">
                            <fieldset>
                                <legend class="form-label mb-2 d-block">Recommended Semesters</legend>
                                <p class="text-secondary small mb-2">Select the academic semesters where this course is scheduled to be taught:</p>
                                <div class="p-3 border rounded bg-light-subtle">
                                    <div class="row g-2">
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <div class="col-6 col-sm-3">
                                                <div class="form-check">
                                                    <input class="form-check-input sem-check" type="checkbox" value="<?= $i ?>" id="sem<?= $i ?>">
                                                    <label class="form-check-label small fw-medium" for="sem<?= $i ?>">
                                                        Semester <?= $i ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-3 px-4 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="saveBtn">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span id="saveBtnText">Save Course</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let courseModalInstance = null;
    let allCourses = [];
    let allLecturers = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('courseModal');
        if (modalEl) {
            courseModalInstance = new bootstrap.Modal(modalEl);
        }
        loadLecturers();
        loadCourses();
    });

    function loadLecturers() {
        fetch('<?= htmlspecialchars(base_url('/api/get_lecturers.php'), ENT_QUOTES, 'UTF-8') ?>')
            .then(res => res.json())
            .then(lecturers => {
                allLecturers = Array.isArray(lecturers) ? lecturers : [];
                const select = document.getElementById('lecturer_id');
                if (select) {
                    select.innerHTML = '<option value="">Select Lecturer (Optional)</option>';
                    allLecturers.forEach(l => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.full_name;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Failed to load lecturers:', err));
    }

    function loadCourses() {
        const tbody = document.getElementById('courseTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-secondary small">Refreshing course list...</span>
                </td>
            </tr>
        `;

        fetch('<?= htmlspecialchars(base_url('/api/get_courses.php'), ENT_QUOTES, 'UTF-8') ?>')
            .then(res => res.json())
            .then(courses => {
                allCourses = Array.isArray(courses) ? courses : [];
                updateStats(allCourses);
                renderCourseRows(allCourses);
            })
            .catch(err => {
                console.error('Failed to load courses:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> Error loading courses. Please refresh.
                        </td>
                    </tr>
                `;
            });
    }

    function updateStats(courses) {
        const total = courses.length;
        const assigned = courses.filter(c => c.lecturer_id && c.lecturer_name).length;
        const unassigned = total - assigned;

        const allSems = new Set();
        courses.forEach(c => {
            if (Array.isArray(c.semesters)) {
                c.semesters.forEach(s => allSems.add(s));
            }
        });

        document.getElementById('statTotalCourses').textContent = total;
        document.getElementById('statAssignedCourses').textContent = assigned;
        document.getElementById('statUnassignedCourses').textContent = unassigned;
        document.getElementById('statActiveSemesters').textContent = allSems.size + ' of 8';
    }

    function filterCourses() {
        const searchTerm = document.getElementById('searchInput').value.trim().toLowerCase();
        const semester = document.getElementById('semesterFilter').value;
        const lecturerFilter = document.getElementById('lecturerFilter').value;

        const filtered = allCourses.filter(c => {
            const matchesSearch = !searchTerm || 
                (c.code && c.code.toLowerCase().includes(searchTerm)) || 
                (c.name && c.name.toLowerCase().includes(searchTerm)) ||
                (c.lecturer_name && c.lecturer_name.toLowerCase().includes(searchTerm));

            const matchesSemester = !semester || 
                (Array.isArray(c.semesters) && c.semesters.includes(parseInt(semester, 10)));

            let matchesLecturer = true;
            if (lecturerFilter === 'assigned') {
                matchesLecturer = Boolean(c.lecturer_id);
            } else if (lecturerFilter === 'unassigned') {
                matchesLecturer = !c.lecturer_id;
            }

            return matchesSearch && matchesSemester && matchesLecturer;
        });

        renderCourseRows(filtered);
    }

    function renderCourseRows(courses) {
        const tbody = document.getElementById('courseTableBody');
        const emptyState = document.getElementById('emptyState');
        const countLabel = document.getElementById('courseCountLabel');

        tbody.innerHTML = '';

        if (courses.length === 0) {
            emptyState.classList.remove('d-none');
            countLabel.textContent = 'Showing 0 courses';
            return;
        }

        emptyState.classList.add('d-none');
        countLabel.textContent = `Showing ${courses.length} course${courses.length === 1 ? '' : 's'}`;

        courses.forEach(c => {
            const tr = document.createElement('tr');

            // Semesters badges
            let semsHtml = '<span class="text-muted small">&mdash;</span>';
            if (Array.isArray(c.semesters) && c.semesters.length > 0) {
                semsHtml = c.semesters.map(s => 
                    `<span class="badge cm-badge-neutral font-monospace me-1">Sem ${escapeHtml(String(s))}</span>`
                ).join('');
            }

            // Lecturer display
            let lecturerHtml = '';
            if (c.lecturer_name) {
                lecturerHtml = `
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person text-secondary" aria-hidden="true"></i>
                        <span class="fw-medium">${escapeHtml(c.lecturer_name)}</span>
                    </div>
                `;
            } else {
                lecturerHtml = `
                    <span class="badge cm-badge-warning">
                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Unassigned
                    </span>
                `;
            }

            tr.innerHTML = `
                <td>
                    <span class="fw-bold font-monospace tabular-nums text-primary">${escapeHtml(c.code)}</span>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(c.name)}</div>
                </td>
                <td class="text-center">
                    <span class="badge cm-badge-primary tabular-nums">${escapeHtml(String(c.credit_hours))} Cr</span>
                </td>
                <td>${lecturerHtml}</td>
                <td>${semsHtml}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-outline-primary btn-sm edit-btn" aria-label="Edit course ${escapeHtml(c.code)}">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                        <span>Edit</span>
                    </button>
                </td>
            `;

            tr.querySelector('.edit-btn').addEventListener('click', () => openEditModal(c));
            tbody.appendChild(tr);
        });
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('semesterFilter').value = '';
        document.getElementById('lecturerFilter').value = '';
        filterCourses();
    }

    function openAddModal() {
        const form = document.getElementById('courseForm');
        form.reset();
        document.getElementById('courseId').value = '';
        document.getElementById('modalTitle').textContent = 'Add Department Course';
        document.getElementById('saveBtnText').textContent = 'Create Course';
        document.getElementById('modalAlert').classList.add('d-none');
        document.querySelectorAll('.sem-check').forEach(chk => chk.checked = false);

        if (courseModalInstance) {
            courseModalInstance.show();
        }
    }

    function openEditModal(course) {
        document.getElementById('courseForm').reset();
        document.getElementById('modalTitle').textContent = 'Edit Course: ' + course.code;
        document.getElementById('saveBtnText').textContent = 'Update Course';
        document.getElementById('modalAlert').classList.add('d-none');

        document.getElementById('courseId').value = course.id;
        document.getElementById('code').value = course.code;
        document.getElementById('name').value = course.name;
        document.getElementById('credit_hours').value = course.credit_hours;
        document.getElementById('lecturer_id').value = course.lecturer_id || '';

        const courseSems = Array.isArray(course.semesters) ? course.semesters.map(Number) : [];
        document.querySelectorAll('.sem-check').forEach(chk => {
            chk.checked = courseSems.includes(parseInt(chk.value, 10));
        });

        if (courseModalInstance) {
            courseModalInstance.show();
        }
    }

    function saveCourse(event) {
        event.preventDefault();
        const alertEl = document.getElementById('modalAlert');
        const alertMsg = document.getElementById('modalAlertMsg');
        alertEl.classList.add('d-none');

        const id = document.getElementById('courseId').value;
        const code = document.getElementById('code').value.trim();
        const name = document.getElementById('name').value.trim();
        const credit_hours = parseInt(document.getElementById('credit_hours').value, 10);
        const lecturer_id = document.getElementById('lecturer_id').value;
        const semesters = Array.from(document.querySelectorAll('.sem-check:checked')).map(chk => parseInt(chk.value, 10));

        if (!code || !name || isNaN(credit_hours) || credit_hours <= 0) {
            alertMsg.textContent = 'Please provide a valid course code, title, and positive credit hours.';
            alertEl.classList.remove('d-none');
            return;
        }

        const payload = {
            id: id || '',
            code: code,
            name: name,
            credit_hours: credit_hours,
            lecturer_id: lecturer_id || null,
            semesters: semesters,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;

        fetch('<?= htmlspecialchars(base_url('/api/save_course.php'), ENT_QUOTES, 'UTF-8') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(resp => {
            saveBtn.disabled = false;
            if (resp.success) {
                if (courseModalInstance) {
                    courseModalInstance.hide();
                }
                loadCourses();
            } else {
                alertMsg.textContent = resp.error || 'Failed to save course. Please check inputs.';
                alertEl.classList.remove('d-none');
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            console.error('Error saving course:', err);
            alertMsg.textContent = 'Network or server error occurred while saving.';
            alertEl.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
