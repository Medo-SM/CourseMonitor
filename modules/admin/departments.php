<?php
// modules/admin/departments.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$pageTitle = 'Departments';
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
            <li class="breadcrumb-item active text-slate-800 fw-medium" aria-current="page">Departments</li>
        </ol>
    </nav>

    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Departments</h1>
            <p class="cm-page-subtitle">Manage academic departments, parent faculties, and appointed Department Heads</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openModal()" class="btn btn-primary" id="btnOpenAddDept">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add Department
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
                    <span class="input-group-text bg-light text-muted border-end-0" id="search-addon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control border-start-0" 
                           placeholder="Search by department, faculty, or head..." 
                           aria-label="Search departments" aria-describedby="search-addon"
                           oninput="filterDepartments()">
                </div>

                <!-- Faculty Filter -->
                <div style="min-width: 220px;">
                    <select id="filter_faculty" class="form-select" onchange="filterDepartments()" aria-label="Filter by faculty">
                        <option value="">All Faculties</option>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Total Departments:</span>
                <span class="badge cm-badge-neutral font-monospace" id="matchCount">0</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0">
                <table class="table cm-table table-hover mb-0" id="deptTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col">Department Name</th>
                            <th scope="col">Faculty</th>
                            <th scope="col">Department Head</th>
                            <th scope="col" class="text-end" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deptTableBody">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading departments...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="cm-card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="recordSummary">Showing academic departments</span>
            <span class="badge cm-badge-neutral small">Department Directory</span>
        </div>
    </div>
</div>

<!-- Add / Edit Department Modal -->
<div class="modal fade" id="deptModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cm-card border-0">
            <div class="modal-header border-bottom">
                <h2 class="modal-title h5 mb-0 fw-semibold text-slate-800" id="modalTitle">Add Department</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <form id="deptForm" onsubmit="saveDepartment(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="id">

                    <!-- Modal Validation Feedback -->
                    <div id="modalAlert" class="alert alert-danger d-none py-2 px-3 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Department Name <span class="required" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted" aria-hidden="true"><i class="bi bi-building"></i></span>
                            <input type="text" id="name" class="form-control" placeholder="e.g. Department of Computer Science" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="faculty_id" class="form-label">
                            Parent Faculty <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select id="faculty_id" class="form-select">
                            <option value="">Select Faculty...</option>
                        </select>
                        <div class="form-text">Choose the collegiate faculty or school this department belongs to.</div>
                    </div>

                    <div class="mb-3">
                        <label for="head_id" class="form-label">
                            Assign Department Head <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <select id="head_id" class="form-select">
                            <option value="">Select Department Head...</option>
                        </select>
                        <div class="form-text">Only users registered with the Department Head role are listed.</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveDept">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let modalInstance = null;
    let allDepts = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('deptModal');
        if (modalEl) {
            modalInstance = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', () => {
                document.getElementById('name').focus();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                const addBtn = document.getElementById('btnOpenAddDept');
                if (addBtn) addBtn.focus();
            });
        }

        loadFaculties();
        loadHeads();
        loadDepartments();
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

    function loadFaculties() {
        fetch('<?= base_url('/api/get_faculties.php') ?>')
            .then(r => r.json())
            .then(list => {
                const formSelect = document.getElementById('faculty_id');
                const filterSelect = document.getElementById('filter_faculty');
                formSelect.innerHTML = '<option value="">Select Faculty...</option>';
                filterSelect.innerHTML = '<option value="">All Faculties</option>';

                if (Array.isArray(list)) {
                    list.forEach(f => {
                        const optForm = document.createElement('option');
                        optForm.value = f.id;
                        optForm.textContent = f.name;
                        formSelect.appendChild(optForm);

                        const optFilter = document.createElement('option');
                        optFilter.value = f.id;
                        optFilter.textContent = f.name;
                        filterSelect.appendChild(optFilter);
                    });
                }
            })
            .catch(err => console.error('Error loading faculties:', err));
    }

    function loadHeads() {
        fetch('<?= base_url('/api/get_users.php') ?>')
            .then(r => r.json())
            .then(users => {
                const select = document.getElementById('head_id');
                select.innerHTML = '<option value="">Select Department Head...</option>';
                if (Array.isArray(users)) {
                    users.filter(u => u.role === 'head').forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = `${u.full_name} (@${u.username})`;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Error loading heads:', err));
    }

    function loadDepartments() {
        fetch('<?= base_url('/api/get_department_details.php') ?>')
            .then(r => r.json())
            .then(depts => {
                allDepts = Array.isArray(depts) ? depts : [];
                filterDepartments();
            })
            .catch(err => {
                console.error('Error loading departments:', err);
                const tbody = document.getElementById('deptTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="cm-empty-state py-4">
                                <div class="cm-empty-icon text-danger" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
                                <h3 class="cm-empty-title">Failed to load departments</h3>
                                <p class="cm-empty-desc">An error occurred while loading departments. Please check your connection.</p>
                                <button type="button" onclick="loadDepartments()" class="btn btn-sm btn-outline-primary">Try Again</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    function filterDepartments() {
        const search = (document.getElementById('searchInput').value || '').trim().toLowerCase();
        const facultyId = document.getElementById('filter_faculty').value;

        const filtered = allDepts.filter(d => {
            const matchesSearch = !search ||
                (d.name && d.name.toLowerCase().includes(search)) ||
                (d.faculty_name && d.faculty_name.toLowerCase().includes(search)) ||
                (d.head_name && d.head_name.toLowerCase().includes(search));

            const matchesFaculty = !facultyId || String(d.faculty_id) === String(facultyId);

            return matchesSearch && matchesFaculty;
        });

        document.getElementById('matchCount').textContent = filtered.length;
        document.getElementById('recordSummary').textContent = `Showing ${filtered.length} of ${allDepts.length} departments`;
        renderDepartments(filtered);
    }

    function renderDepartments(depts) {
        const tbody = document.getElementById('deptTableBody');
        tbody.innerHTML = '';

        if (depts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="cm-empty-state py-4">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-building"></i></div>
                            <h3 class="cm-empty-title">No Departments Found</h3>
                            <p class="cm-empty-desc">No departments match the current search or filters. Adjust filters or add a new department.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        depts.forEach((d, idx) => {
            const deptJson = JSON.stringify(d).replace(/"/g, '&quot;');

            let facultyCol = '<span class="text-muted">—</span>';
            if (d.faculty_name) {
                facultyCol = `<span class="badge cm-badge-neutral text-slate-800"><i class="bi bi-diagram-3 me-1 text-muted" aria-hidden="true"></i>${escapeHtml(d.faculty_name)}</span>`;
            }

            let headCol = '<span class="badge cm-badge-warning"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Not Assigned</span>';
            if (d.head_name) {
                headCol = `<span class="fw-medium text-slate-900"><i class="bi bi-person-badge me-1 text-muted" aria-hidden="true"></i>${escapeHtml(d.head_name)}</span>`;
            }

            tbody.innerHTML += `
                <tr>
                    <td class="font-monospace text-muted small">${idx + 1}</td>
                    <td>
                        <div class="fw-semibold text-slate-900">
                            <i class="bi bi-building me-1 text-primary" aria-hidden="true"></i>${escapeHtml(d.name)}
                        </div>
                    </td>
                    <td>${facultyCol}</td>
                    <td>${headCol}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Department actions">
                            <button type="button" onclick="editDept(${deptJson})" class="btn btn-outline-primary" aria-label="Edit department ${escapeHtml(d.name)}">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                            </button>
                            <button type="button" onclick="deleteDept(${Number(d.id)}, '${escapeHtml(d.name)}')" class="btn btn-outline-danger" aria-label="Delete department ${escapeHtml(d.name)}">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function openModal() {
        document.getElementById('deptForm').reset();
        document.getElementById('id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Department';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function editDept(dept) {
        document.getElementById('modalTitle').textContent = `Edit Department: ${dept.name}`;
        document.getElementById('id').value = dept.id;
        document.getElementById('name').value = dept.name;
        document.getElementById('faculty_id').value = dept.faculty_id || '';
        document.getElementById('head_id').value = dept.head_id || '';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        modalInstance.show();
    }

    function deleteDept(id, name) {
        if (!confirm(`Are you sure you want to delete department "${name}"? This action cannot be undone.`)) {
            return;
        }

        fetch('<?= base_url('/api/delete_department.php') ?>', {
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
                showAlert(`Department "${name}" was deleted successfully.`, 'success');
                loadDepartments();
            } else {
                showAlert(resp.error || 'Failed to delete department.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Network error during department deletion.', 'danger');
        });
    }

    function saveDepartment(event) {
        event.preventDefault();
        const modalAlert = document.getElementById('modalAlert');
        modalAlert.className = 'alert alert-danger d-none py-2 px-3 small';

        const id = document.getElementById('id').value;
        const name = document.getElementById('name').value.trim();
        const facultyId = document.getElementById('faculty_id').value;
        const headId = document.getElementById('head_id').value;

        if (!name) {
            modalAlert.textContent = 'Please enter a Department Name.';
            modalAlert.classList.remove('d-none');
            return;
        }

        const data = {
            id: id,
            name: name,
            faculty_id: facultyId,
            head_id: headId,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('btnSaveDept');
        const originalBtnText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

        fetch('<?= base_url('/api/save_department.php') ?>', {
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
                showAlert(`Department "${name}" was saved successfully.`, 'success');
                loadDepartments();
            } else {
                modalAlert.textContent = resp.error || 'Failed to save department.';
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;
            modalAlert.textContent = 'Network error while saving department.';
            modalAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
