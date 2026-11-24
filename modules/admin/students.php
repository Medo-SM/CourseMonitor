<?php
// modules/admin/students.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$pageTitle = 'Students';
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
            <li class="breadcrumb-item active fw-medium" aria-current="page">Students</li>
        </ol>
    </nav>

    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Students</h1>
            <p class="cm-page-subtitle">View enrolled students, update semester progression, and manage records</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openModal()" class="btn btn-primary" id="btnOpenAddStudent">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add Student
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
                           placeholder="Search by name, student ID, or email..." 
                           aria-label="Search students" aria-describedby="search-addon"
                           oninput="filterStudents()">
                </div>

                <!-- Department Filter -->
                <div style="min-width: 200px;">
                    <select id="filterDept" class="form-select" onchange="filterStudents()" aria-label="Filter by department">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div style="min-width: 140px;">
                    <select id="filterSem" class="form-select" onchange="filterStudents()" aria-label="Filter by semester">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Total Students:</span>
                <span class="badge cm-badge-neutral font-monospace" id="matchCount">0</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0">
                <table class="table cm-table table-hover mb-0" id="studentTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col" style="width: 160px;">Student ID</th>
                            <th scope="col">Full Name</th>
                            <th scope="col">Email</th>
                            <th scope="col" style="width: 130px;">Semester</th>
                            <th scope="col">Department</th>
                            <th scope="col" class="text-end" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading students...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cm-card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="recordSummary">Showing student records</span>
            <span class="badge cm-badge-neutral small">Student Directory</span>
        </div>
    </div>
</div>

<!-- Add / Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cm-card border-0">
            <div class="modal-header border-bottom">
                <h2 class="modal-title h5 mb-0 fw-semibold" id="modalTitle">Add Student</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <form id="studentForm" onsubmit="saveStudent(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="id">

                    <!-- Modal Validation Feedback -->
                    <div id="modalAlert" class="alert alert-danger d-none py-2 px-3 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="student_id_number" class="form-label">
                            Student ID Number <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-card-text"></i></span>
                            <input type="text" id="student_id_number" class="form-control font-monospace" placeholder="e.g. STU-2026-0042" required autocomplete="off">
                        </div>
                        <div class="form-text">Unique student matriculation or ID number.</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Full Name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="name" class="form-control" placeholder="e.g. Jane Doe" required autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email Address <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" class="form-control" placeholder="e.g. jdoe@example.edu" required autocomplete="email">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 mb-3">
                            <label for="current_semester" class="form-label">
                                Current Semester <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="current_semester" class="form-select" required>
                                <?php for ($s = 1; $s <= 12; $s++): ?>
                                    <option value="<?= $s ?>">Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label for="department_id" class="form-label">
                                Department <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="department_id" class="form-select" required>
                                <option value="">Select Department...</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveStudent">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let modalInstance = null;
    let allStudents = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('studentModal');
        if (modalEl) {
            modalInstance = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', () => {
                document.getElementById('student_id_number').focus();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                const addBtn = document.getElementById('btnOpenAddStudent');
                if (addBtn) addBtn.focus();
            });
        }

        loadDepartments();
        loadStudents();
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
                const modalSelect = document.getElementById('department_id');
                const filterSelect = document.getElementById('filterDept');
                modalSelect.innerHTML = '<option value="">Select Department...</option>';
                filterSelect.innerHTML = '<option value="">All Departments</option>';

                if (Array.isArray(depts)) {
                    depts.forEach(d => {
                        const optModal = document.createElement('option');
                        optModal.value = d.id;
                        optModal.textContent = d.name;
                        modalSelect.appendChild(optModal);

                        const optFilter = document.createElement('option');
                        optFilter.value = d.id;
                        optFilter.textContent = d.name;
                        filterSelect.appendChild(optFilter);
                    });
                }
            })
            .catch(err => console.error('Error loading departments:', err));
    }

    function loadStudents() {
        fetch('<?= base_url('/api/get_students_manage.php') ?>')
            .then(r => r.json())
            .then(students => {
                allStudents = Array.isArray(students) ? students : [];
                filterStudents();
            })
            .catch(err => {
                console.error('Error loading students:', err);
                const tbody = document.getElementById('studentTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="cm-empty-state py-4">
                                <div class="cm-empty-icon text-danger" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
                                <h3 class="cm-empty-title">Failed to load students</h3>
                                <p class="cm-empty-desc">An error occurred while loading students. Please check your connection.</p>
                                <button type="button" onclick="loadStudents()" class="btn btn-sm btn-outline-primary">Try Again</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    function filterStudents() {
        const search = (document.getElementById('searchInput').value || '').trim().toLowerCase();
        const deptId = document.getElementById('filterDept').value;
        const sem = document.getElementById('filterSem').value;

        const filtered = allStudents.filter(s => {
            const matchesSearch = !search ||
                (s.student_id_number && s.student_id_number.toLowerCase().includes(search)) ||
                (s.name && s.name.toLowerCase().includes(search)) ||
                (s.email && s.email.toLowerCase().includes(search));

            const matchesDept = !deptId || String(s.department_id) === String(deptId);
            const matchesSem = !sem || String(s.current_semester) === String(sem);

            return matchesSearch && matchesDept && matchesSem;
        });

        document.getElementById('matchCount').textContent = filtered.length;
        document.getElementById('recordSummary').textContent = `Showing ${filtered.length} of ${allStudents.length} students`;
        renderStudents(filtered);
    }

    function renderStudents(students) {
        const tbody = document.getElementById('studentTableBody');
        tbody.innerHTML = '';

        if (students.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="cm-empty-state py-4">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></div>
                            <h3 class="cm-empty-title">No Students Found</h3>
                            <p class="cm-empty-desc">No student records match the active search or filters. Adjust filters or add a new student.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        students.forEach((s, idx) => {
            const studentJson = JSON.stringify(s).replace(/"/g, '&quot;');
            const semNum = s.current_semester || 1;

            tbody.innerHTML += `
                <tr>
                    <td class="font-monospace text-muted small">${idx + 1}</td>
                    <td>
                        <span class="badge cm-badge-neutral font-monospace fw-semibold" style="letter-spacing: 0.04em;">
                            <i class="bi bi-person-vcard me-1" aria-hidden="true"></i>${escapeHtml(s.student_id_number)}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(s.name)}</div>
                    </td>
                    <td>
                        <span class="font-monospace small text-secondary">${escapeHtml(s.email)}</span>
                    </td>
                    <td>
                        <span class="badge cm-badge-primary font-monospace">Semester ${escapeHtml(semNum)}</span>
                    </td>
                    <td>
                        <span>
                            <i class="bi bi-building me-1 text-muted" aria-hidden="true"></i>${escapeHtml(s.department_name || 'N/A')}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Student actions">
                            <button type="button" onclick="editStudent(${studentJson})" class="btn btn-outline-primary" aria-label="Edit student ${escapeHtml(s.name)}">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                            </button>
                            <button type="button" onclick="deleteStudent(${Number(s.id)}, '${escapeHtml(s.name)}')" class="btn btn-outline-danger" aria-label="Delete student ${escapeHtml(s.name)}">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function openModal() {
        document.getElementById('studentForm').reset();
        document.getElementById('id').value = '';
        document.getElementById('current_semester').value = '1';
        document.getElementById('modalTitle').textContent = 'Add Student';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function editStudent(student) {
        document.getElementById('modalTitle').textContent = `Edit Student: ${student.name}`;
        document.getElementById('id').value = student.id;
        document.getElementById('student_id_number').value = student.student_id_number;
        document.getElementById('name').value = student.name;
        document.getElementById('email').value = student.email;
        document.getElementById('current_semester').value = student.current_semester || 1;
        document.getElementById('department_id').value = student.department_id || '';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function deleteStudent(id, name) {
        if (!confirm(`Are you sure you want to delete student "${name}"? This action cannot be undone.`)) {
            return;
        }

        fetch('<?= base_url('/api/delete_student.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                csrf_token: '<?= generateCsrfToken() ?>'
            })
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                showAlert(`Student "${name}" was deleted successfully.`, 'success');
                loadStudents();
            } else {
                showAlert(resp.error || 'Failed to delete student.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Network error during student deletion.', 'danger');
        });
    }

    function saveStudent(event) {
        event.preventDefault();
        const modalAlert = document.getElementById('modalAlert');
        modalAlert.className = 'alert alert-danger d-none py-2 px-3 small';

        const id = document.getElementById('id').value;
        const studentIdNum = document.getElementById('student_id_number').value.trim();
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const semester = document.getElementById('current_semester').value;
        const deptId = document.getElementById('department_id').value;

        if (!studentIdNum || !name || !email || !deptId) {
            modalAlert.textContent = 'Please fill in all required fields, including Department.';
            modalAlert.classList.remove('d-none');
            return;
        }

        const data = {
            id: id,
            student_id_number: studentIdNum,
            name: name,
            email: email,
            current_semester: semester,
            department_id: deptId,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('btnSaveStudent');
        const originalBtnText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

        fetch('<?= base_url('/api/save_student.php') ?>', {
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
                showAlert(`Student "${name}" was saved successfully.`, 'success');
                loadStudents();
            } else {
                modalAlert.textContent = resp.error || 'Failed to save student.';
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;
            modalAlert.textContent = 'Network error while saving student.';
            modalAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
