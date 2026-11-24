<?php
// modules/admin/courses.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$pageTitle = 'Courses';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="Breadcrumb" class="mb-2">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars(base_url('/modules/admin/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-secondary text-decoration-none">
                    <i class="bi bi-house me-1" aria-hidden="true"></i>Admin
                </a>
            </li>
            <li class="breadcrumb-item active fw-medium" aria-current="page">Courses</li>
        </ol>
    </nav>

    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Courses</h1>
            <p class="cm-page-subtitle">Create and organize courses, assign teachers, and allocate credit hours</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openModal()" class="btn btn-primary" id="btnOpenAddCourse">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add Course
            </button>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer" class="d-none"></div>

    <!-- Data Matrix Card -->
    <div class="cm-card">
        <!-- Filter Toolbar -->
        <div class="cm-card-header flex-column flex-md-row align-items-stretch align-items-md-center gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <!-- Search Input -->
                <div class="input-group" style="max-width: 320px;">
                    <span class="input-group-text border-end-0" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0" 
                           placeholder="Search by course code or title..." 
                           aria-label="Search courses" aria-describedby="search-addon"
                           oninput="filterCourses()">
                </div>

                <!-- Department Filter -->
                <div style="min-width: 220px;">
                    <select id="filter_dept_id" class="form-select" onchange="filterCourses()" aria-label="Filter by department">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div style="min-width: 140px;">
                    <select id="filter_semester" class="form-select" onchange="filterCourses()" aria-label="Filter by semester">
                        <option value="">All Semesters</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>">Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Total Courses:</span>
                <span class="badge cm-badge-neutral font-monospace" id="matchCount">0</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0">
                <table class="table cm-table table-hover mb-0" id="courseTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 140px;">Course Code</th>
                            <th scope="col">Course Title</th>
                            <th scope="col" style="width: 100px;" class="text-center">Credits</th>
                            <th scope="col">Department</th>
                            <th scope="col">Assigned Teacher</th>
                            <th scope="col">Semesters</th>
                            <th scope="col" class="text-end" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="courseTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading courses...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cm-card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="recordSummary">Showing course offerings</span>
            <span class="badge cm-badge-neutral small">Course Catalog</span>
        </div>
    </div>
</div>

<!-- Add / Edit Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content cm-card border-0">
            <div class="modal-header border-bottom">
                <h2 class="modal-title h5 mb-0 fw-semibold" id="modalTitle">Add Course</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <form id="courseForm" onsubmit="saveCourse(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="id">

                    <!-- Modal Validation Feedback -->
                    <div id="modalAlert" class="alert alert-danger d-none py-2 px-3 small" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-5 mb-3">
                            <label for="code" class="form-label">
                                Course Code <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="code" class="form-control font-monospace" placeholder="e.g. CS101" required autocomplete="off">
                            <div class="form-text">Unique course identifier code.</div>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="name" class="form-label">
                                Course Title <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="name" class="form-control" placeholder="e.g. Introduction to Programming" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4 mb-3">
                            <label for="credit_hours" class="form-label">
                                Credit Hours <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="number" id="credit_hours" class="form-control" required min="1" max="10" value="3">
                            <div class="form-text">Number of academic credits.</div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="department_id" class="form-label">
                                Department <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="department_id" class="form-select" required>
                                <option value="">Select Department...</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lecturer_id" class="form-label">
                            Assign Teacher <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select id="lecturer_id" class="form-select">
                            <option value="">Select Teacher...</option>
                        </select>
                        <div class="form-text">The instructor who takes attendance and enters student grades for this course.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">
                            Available Semesters <span class="text-muted fw-normal">(Select all that apply)</span>
                        </label>
                        <div class="row g-2 p-3 border rounded" style="background-color: var(--cm-bg-subtle);">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <div class="col-6 col-sm-3">
                                    <div class="form-check">
                                        <input class="form-check-input sem-check" type="checkbox" value="<?= $i ?>" id="sem<?= $i ?>">
                                        <label class="form-check-label small user-select-none" for="sem<?= $i ?>">
                                            Semester <?= $i ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveCourse">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let modalInstance = null;
    let allCourses = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('courseModal');
        if (modalEl) {
            modalInstance = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', () => {
                document.getElementById('code').focus();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                const addBtn = document.getElementById('btnOpenAddCourse');
                if (addBtn) addBtn.focus();
            });
        }

        loadDepartments();
        loadLecturers();
        loadCourses();
    });

    function showAlert(message, type = 'success') {
        const c = document.getElementById('alertContainer');
        c.className = `alert cm-alert-${type} alert-dismissible fade show mb-4`;
        c.setAttribute('role', 'status');
        c.innerHTML = `
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'} me-2" aria-hidden="true"></i>
            <div>${escapeHtml(message)}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
    }

    function loadDepartments() {
        fetch('<?= base_url('/api/get_departments.php') ?>')
            .then(r => r.json())
            .then(depts => {
                const filterSelect = document.getElementById('filter_dept_id');
                const formSelect = document.getElementById('department_id');
                filterSelect.innerHTML = '<option value="">All Departments</option>';
                formSelect.innerHTML = '<option value="">Select Department...</option>';

                if (Array.isArray(depts)) {
                    depts.forEach(d => {
                        const optFilter = document.createElement('option');
                        optFilter.value = d.id;
                        optFilter.textContent = d.name;
                        filterSelect.appendChild(optFilter);

                        const optForm = document.createElement('option');
                        optForm.value = d.id;
                        optForm.textContent = d.name;
                        formSelect.appendChild(optForm);
                    });
                }
            })
            .catch(err => console.error('Error loading departments:', err));
    }

    function loadLecturers() {
        fetch('<?= base_url('/api/get_lecturers.php') ?>')
            .then(r => r.json())
            .then(lecturers => {
                const select = document.getElementById('lecturer_id');
                select.innerHTML = '<option value="">Select Teacher...</option>';
                if (Array.isArray(lecturers)) {
                    lecturers.forEach(l => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.full_name;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Error loading lecturers:', err));
    }

    function loadCourses() {
        fetch('<?= base_url('/api/get_courses.php') ?>')
            .then(r => r.json())
            .then(courses => {
                allCourses = Array.isArray(courses) ? courses : [];
                filterCourses();
            })
            .catch(err => {
                console.error('Error loading courses:', err);
                const tbody = document.getElementById('courseTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="cm-empty-state py-4">
                                <div class="cm-empty-icon text-danger" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
                                <h3 class="cm-empty-title">Failed to load courses</h3>
                                <p class="cm-empty-desc">An error occurred while loading courses. Please check your connection.</p>
                                <button type="button" onclick="loadCourses()" class="btn btn-sm btn-outline-primary">Try Again</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    function filterCourses() {
        const search = (document.getElementById('searchInput').value || '').trim().toLowerCase();
        const deptId = document.getElementById('filter_dept_id').value;
        const sem = document.getElementById('filter_semester').value;

        const filtered = allCourses.filter(c => {
            const matchesSearch = !search ||
                (c.code && c.code.toLowerCase().includes(search)) ||
                (c.name && c.name.toLowerCase().includes(search));

            const matchesDept = !deptId || String(c.department_id) === String(deptId);
            
            let matchesSem = true;
            if (sem) {
                const semInt = parseInt(sem, 10);
                matchesSem = Array.isArray(c.semesters) && c.semesters.includes(semInt);
            }

            return matchesSearch && matchesDept && matchesSem;
        });

        document.getElementById('matchCount').textContent = filtered.length;
        document.getElementById('recordSummary').textContent = `Showing ${filtered.length} of ${allCourses.length} courses`;
        renderCourses(filtered);
    }

    function renderCourses(courses) {
        const tbody = document.getElementById('courseTableBody');
        tbody.innerHTML = '';

        if (courses.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="cm-empty-state py-4">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-book"></i></div>
                            <h3 class="cm-empty-title">No Courses Found</h3>
                            <p class="cm-empty-desc">No courses match the active search or filters. Adjust filters or add a new course.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        courses.forEach(c => {
            const courseJson = JSON.stringify(c).replace(/"/g, '&quot;');
            
            let lecturerBadge = '<span class="badge cm-badge-warning"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Unassigned</span>';
            if (c.lecturer_name) {
                lecturerBadge = `<span class="fw-medium"><i class="bi bi-person me-1 text-muted" aria-hidden="true"></i>${escapeHtml(c.lecturer_name)}</span>`;
            }

            let semBadges = '<span class="text-muted small">—</span>';
            if (Array.isArray(c.semesters) && c.semesters.length > 0) {
                semBadges = c.semesters.map(s => `<span class="badge cm-badge-neutral font-monospace me-1">Sem ${s}</span>`).join('');
            }

            tbody.innerHTML += `
                <tr>
                    <td>
                        <span class="badge cm-badge-neutral font-monospace fw-semibold" style="letter-spacing: 0.04em;">
                            ${escapeHtml(c.code)}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(c.name)}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge cm-badge-primary font-monospace">${escapeHtml(c.credit_hours)} Cr</span>
                    </td>
                    <td>
                        <span>
                            <i class="bi bi-building me-1 text-muted" aria-hidden="true"></i>${escapeHtml(c.department_name || 'N/A')}
                        </span>
                    </td>
                    <td>${lecturerBadge}</td>
                    <td>${semBadges}</td>
                    <td class="text-end">
                        <button type="button" onclick="editCourse(${courseJson})" class="btn btn-sm btn-outline-primary" aria-label="Edit course ${escapeHtml(c.code)}">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function openModal() {
        document.getElementById('courseForm').reset();
        document.getElementById('id').value = '';
        document.getElementById('credit_hours').value = '3';
        document.querySelectorAll('.sem-check').forEach(c => c.checked = false);
        document.getElementById('modalTitle').textContent = 'Add Course';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';

        // Auto-select department if filtered
        const currentFilter = document.getElementById('filter_dept_id').value;
        if (currentFilter) {
            document.getElementById('department_id').value = currentFilter;
        }

        modalInstance.show();
    }

    function editCourse(course) {
        document.getElementById('modalTitle').textContent = `Edit Course: ${course.code}`;
        document.getElementById('id').value = course.id;
        document.getElementById('code').value = course.code;
        document.getElementById('name').value = course.name;
        document.getElementById('credit_hours').value = course.credit_hours;
        document.getElementById('department_id').value = course.department_id;
        document.getElementById('lecturer_id').value = course.lecturer_id || '';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';

        const sems = Array.isArray(course.semesters) ? course.semesters : [];
        document.querySelectorAll('.sem-check').forEach(c => {
            c.checked = sems.includes(parseInt(c.value, 10));
        });

        modalInstance.show();
    }

    function saveCourse(event) {
        event.preventDefault();
        const modalAlert = document.getElementById('modalAlert');
        modalAlert.className = 'alert alert-danger d-none py-2 px-3 small';

        const id = document.getElementById('id').value;
        const code = document.getElementById('code').value.trim();
        const name = document.getElementById('name').value.trim();
        const creditHours = parseInt(document.getElementById('credit_hours').value, 10);
        const deptId = document.getElementById('department_id').value;
        const lecturerId = document.getElementById('lecturer_id').value;
        const semesters = Array.from(document.querySelectorAll('.sem-check:checked')).map(c => parseInt(c.value, 10));

        if (!code || !name || isNaN(creditHours) || creditHours <= 0 || !deptId) {
            modalAlert.textContent = 'Please fill in all required fields (Code, Title, valid Credits, and Department).';
            modalAlert.classList.remove('d-none');
            return;
        }

        const data = {
            id: id,
            code: code,
            name: name,
            credit_hours: creditHours,
            department_id: deptId,
            lecturer_id: lecturerId,
            semesters: semesters,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('btnSaveCourse');
        const originalBtnText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

        fetch('<?= base_url('/api/save_course.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(resp => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;

            if (resp.success) {
                modalInstance.hide();
                showAlert(`Course "${code} - ${name}" was saved successfully.`, 'success');
                loadCourses();
            } else {
                modalAlert.textContent = resp.error || 'Failed to save course.';
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;
            modalAlert.textContent = 'Network error while saving course.';
            modalAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
