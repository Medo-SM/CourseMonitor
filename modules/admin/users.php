<?php
// modules/admin/users.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');

$pageTitle = 'Users';
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
            <li class="breadcrumb-item active fw-medium" aria-current="page">Users</li>
        </ol>
    </nav>

    <div class="cm-page-header">
        <div>
            <h1 class="cm-page-title">Users</h1>
            <p class="cm-page-subtitle">Manage user accounts, assign roles, and set department or faculty affiliations</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="openModal()" class="btn btn-primary" id="btnOpenAddUser">
                <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add User
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
                           placeholder="Search by name, email, or username..." 
                           aria-label="Search users" aria-describedby="search-addon"
                           oninput="filterUsers()">
                </div>

                <!-- Role Filter -->
                <div style="min-width: 170px;">
                    <select id="roleFilter" class="form-select" onchange="filterUsers()" aria-label="Filter by role">
                        <option value="">All Roles</option>
                        <option value="lecturer">Teachers</option>
                        <option value="head">Department Heads</option>
                        <option value="admin">Administrators</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div style="min-width: 140px;">
                    <select id="statusFilter" class="form-select" onchange="filterUsers()" aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 text-muted small">
                <span>Total Users:</span>
                <span class="badge cm-badge-neutral font-monospace" id="matchCount">0</span>
            </div>
        </div>

        <div class="cm-card-body p-0">
            <div class="table-responsive border-0">
                <table class="table cm-table table-hover mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Affiliation</th>
                            <th scope="col" style="width: 110px;">Status</th>
                            <th scope="col" class="text-end" style="width: 170px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading users...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="cm-card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="recordSummary">Showing user accounts</span>
            <span class="badge cm-badge-neutral small">User Directory</span>
        </div>
    </div>
</div>

<!-- Add / Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cm-card border-0">
            <div class="modal-header border-bottom">
                <h2 class="modal-title h5 mb-0 fw-semibold" id="modalTitle">Add User</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" id="userId">

                    <!-- Modal Alert -->
                    <div id="modalAlert" class="alert alert-danger d-none py-2 px-3 small" role="alert"></div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="required" aria-hidden="true">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-at"></i></span>
                            <input type="text" id="username" class="form-control" placeholder="e.g. jsmith" required autocomplete="username">
                        </div>
                        <div class="form-text">Used to sign in to the system.</div>
                    </div>

                    <div class="mb-3">
                        <label for="fullName" class="form-label">Full Name <span class="required" aria-hidden="true">*</span></label>
                        <input type="text" id="fullName" class="form-control" placeholder="e.g. Dr. John Smith" required autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="required" aria-hidden="true">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" class="form-control" placeholder="e.g. jsmith@example.edu" required autocomplete="email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="required" aria-hidden="true">*</span></label>
                        <select id="role" class="form-select" required onchange="toggleAffiliation()">
                            <option value="lecturer">Teacher (Lecturer)</option>
                            <option value="head">Department Head</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <div class="form-text">Controls what features and sections this user can access.</div>
                    </div>

                    <!-- Department selection for Department Heads -->
                    <div class="mb-3" id="deptGroup" style="display: none;">
                        <label for="department_id" class="form-label">Department <span class="required" aria-hidden="true">*</span></label>
                        <select id="department_id" class="form-select">
                            <option value="">Select Department...</option>
                        </select>
                        <div class="form-text">Choose the department this head oversees.</div>
                    </div>

                    <!-- Faculty selection for Lecturers -->
                    <div class="mb-3" id="facultyGroup" style="display: none;">
                        <label for="faculty_id" class="form-label">Faculty <span class="required" aria-hidden="true">*</span></label>
                        <select id="faculty_id" class="form-select">
                            <option value="">Select Faculty...</option>
                        </select>
                        <div class="form-text">Choose the faculty division this teacher belongs to.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <span class="text-muted fw-normal" id="pwdHelpText">(Required for new users)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true"><i class="bi bi-key"></i></span>
                            <input type="password" id="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                        </div>
                        <div class="form-text">For existing users, leave blank to keep the current password.</div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input type="checkbox" id="isActive" class="form-check-input" checked role="switch">
                        <label class="form-check-label fw-medium" for="isActive">Active Account</label>
                        <div class="form-text">Inactive accounts cannot sign in to CourseMonitor.</div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveUser">
                        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(base_url('/public/js/utils.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    let modalInstance = null;
    let allUsers = [];

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('userModal');
        if (modalEl) {
            modalInstance = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', () => {
                document.getElementById('username').focus();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                const addBtn = document.getElementById('btnOpenAddUser');
                if (addBtn) addBtn.focus();
            });
        }

        loadDepartments();
        loadFaculties();
        loadUsers();
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
                const select = document.getElementById('department_id');
                select.innerHTML = '<option value="">Select Department...</option>';
                if (Array.isArray(depts)) {
                    depts.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Error loading departments:', err));
    }

    function loadFaculties() {
        fetch('<?= base_url('/api/get_faculties.php') ?>')
            .then(r => r.json())
            .then(list => {
                const select = document.getElementById('faculty_id');
                select.innerHTML = '<option value="">Select Faculty...</option>';
                if (Array.isArray(list)) {
                    list.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.name;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Error loading faculties:', err));
    }

    function loadUsers() {
        fetch('<?= base_url('/api/get_users.php') ?>')
            .then(r => r.json())
            .then(users => {
                allUsers = Array.isArray(users) ? users : [];
                filterUsers();
            })
            .catch(err => {
                console.error('Error loading users:', err);
                const tbody = document.getElementById('userTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="cm-empty-state py-4">
                                <div class="cm-empty-icon text-danger" aria-hidden="true"><i class="bi bi-exclamation-octagon"></i></div>
                                <h3 class="cm-empty-title">Failed to load users</h3>
                                <p class="cm-empty-desc">An error occurred while loading user accounts. Please check your connection.</p>
                                <button type="button" onclick="loadUsers()" class="btn btn-sm btn-outline-primary">Try Again</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    function filterUsers() {
        const search = (document.getElementById('searchInput').value || '').trim().toLowerCase();
        const role = document.getElementById('roleFilter').value;
        const status = document.getElementById('statusFilter').value;

        const filtered = allUsers.filter(u => {
            const matchesSearch = !search ||
                (u.username && u.username.toLowerCase().includes(search)) ||
                (u.full_name && u.full_name.toLowerCase().includes(search)) ||
                (u.email && u.email.toLowerCase().includes(search));

            const matchesRole = !role || u.role === role;
            const matchesStatus = status === '' || String(u.is_active) === status;

            return matchesSearch && matchesRole && matchesStatus;
        });

        document.getElementById('matchCount').textContent = filtered.length;
        document.getElementById('recordSummary').textContent = `Showing ${filtered.length} of ${allUsers.length} users`;
        renderUsers(filtered);
    }

    function renderUsers(users) {
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = '';

        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="cm-empty-state py-4">
                            <div class="cm-empty-icon" aria-hidden="true"><i class="bi bi-people"></i></div>
                            <h3 class="cm-empty-title">No Users Found</h3>
                            <p class="cm-empty-desc">No accounts match the current search or filters. Try adjusting your query or add a new user.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        users.forEach((user, idx) => {
            let aff = '<span class="text-muted">—</span>';
            if (user.role === 'head') {
                aff = user.department_name ? `<span class="fw-medium"><i class="bi bi-building me-1 text-muted"></i>${escapeHtml(user.department_name)}</span>` : '<span class="text-muted">Unassigned</span>';
            } else if (user.role === 'lecturer') {
                aff = user.faculty_name ? `<span class="fw-medium"><i class="bi bi-diagram-3 me-1 text-muted"></i>${escapeHtml(user.faculty_name)}</span>` : '<span class="text-muted">Unassigned</span>';
            }

            let roleBadge = '<span class="badge cm-badge-neutral">User</span>';
            if (user.role === 'admin') {
                roleBadge = '<span class="badge cm-badge-primary"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Admin</span>';
            } else if (user.role === 'head') {
                roleBadge = '<span class="badge cm-badge-info"><i class="bi bi-building me-1" aria-hidden="true"></i>Head</span>';
            } else if (user.role === 'lecturer') {
                roleBadge = '<span class="badge cm-badge-neutral"><i class="bi bi-person me-1" aria-hidden="true"></i>Teacher</span>';
            }

            const statusBadge = user.is_active == 1 
                ? '<span class="badge cm-badge-success"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>Active</span>'
                : '<span class="badge cm-badge-danger"><i class="bi bi-dash-circle me-1" aria-hidden="true"></i>Inactive</span>';

            const userJson = JSON.stringify(user).replace(/"/g, '&quot;');

            tbody.innerHTML += `
                <tr>
                    <td class="font-monospace text-muted small">${idx + 1}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="cm-user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem;" aria-hidden="true">
                                ${escapeHtml((user.full_name || user.username || 'U').charAt(0).toUpperCase())}
                            </div>
                            <div>
                                <div class="fw-semibold">${escapeHtml(user.full_name)}</div>
                                <div class="font-monospace small text-muted">@${escapeHtml(user.username)}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-monospace small text-secondary">${escapeHtml(user.email)}</span>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${aff}</td>
                    <td>${statusBadge}</td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm" role="group" aria-label="User actions">
                            <button type="button" onclick="editUser(${userJson})" class="btn btn-outline-primary" aria-label="Edit ${escapeHtml(user.full_name)}">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit
                            </button>
                            <button type="button" onclick="deleteUser(${Number(user.id)}, '${escapeHtml(user.full_name)}')" class="btn btn-outline-danger" aria-label="Delete ${escapeHtml(user.full_name)}">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    function toggleAffiliation() {
        const role = document.getElementById('role').value;
        const deptGroup = document.getElementById('deptGroup');
        const facultyGroup = document.getElementById('facultyGroup');

        deptGroup.style.display = role === 'head' ? 'block' : 'none';
        facultyGroup.style.display = role === 'lecturer' ? 'block' : 'none';

        document.getElementById('department_id').required = role === 'head';
        document.getElementById('faculty_id').required = role === 'lecturer';
    }

    function openModal() {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('pwdHelpText').textContent = '(Required for new accounts)';
        document.getElementById('password').required = true;
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';
        toggleAffiliation();
        modalInstance.show();
    }

    function editUser(user) {
        document.getElementById('modalTitle').textContent = `Edit User: ${user.full_name}`;
        document.getElementById('userId').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('fullName').value = user.full_name;
        document.getElementById('email').value = user.email;
        document.getElementById('role').value = user.role;
        document.getElementById('department_id').value = user.department_id || '';
        document.getElementById('faculty_id').value = user.faculty_id || '';
        document.getElementById('isActive').checked = user.is_active == 1;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('pwdHelpText').textContent = '(Leave blank to keep existing password)';
        document.getElementById('modalAlert').className = 'alert alert-danger d-none py-2 px-3 small';

        toggleAffiliation();
        modalInstance.show();
    }

    function deleteUser(id, name) {
        if (!confirm(`Are you sure you want to permanently delete user "${name}"? This action cannot be undone.`)) {
            return;
        }

        fetch('<?= base_url('/api/delete_user.php') ?>', {
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
                showAlert(`User "${name}" was deleted successfully.`, 'success');
                loadUsers();
            } else {
                showAlert(resp.error || 'Failed to delete user.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Network error during deletion.', 'danger');
        });
    }

    function saveUser(event) {
        event.preventDefault();
        const modalAlert = document.getElementById('modalAlert');
        modalAlert.className = 'alert alert-danger d-none py-2 px-3 small';

        const id = document.getElementById('userId').value;
        const role = document.getElementById('role').value;
        const deptId = document.getElementById('department_id').value;
        const facultyId = document.getElementById('faculty_id').value;
        const password = document.getElementById('password').value;

        if (role === 'head' && !deptId) {
            modalAlert.textContent = 'Please choose a department for this Department Head.';
            modalAlert.classList.remove('d-none');
            return;
        }
        if (role === 'lecturer' && !facultyId) {
            modalAlert.textContent = 'Please choose a faculty for this Teacher.';
            modalAlert.classList.remove('d-none');
            return;
        }
        if (!id && !password) {
            modalAlert.textContent = 'A password is required for new user accounts.';
            modalAlert.classList.remove('d-none');
            return;
        }

        const data = {
            id: id,
            username: document.getElementById('username').value.trim(),
            full_name: document.getElementById('fullName').value.trim(),
            email: document.getElementById('email').value.trim(),
            role: role,
            department_id: deptId,
            faculty_id: facultyId,
            password: password,
            is_active: document.getElementById('isActive').checked ? 1 : 0,
            csrf_token: '<?= generateCsrfToken() ?>'
        };

        const saveBtn = document.getElementById('btnSaveUser');
        const originalBtnText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';

        fetch('<?= base_url('/api/save_user.php') ?>', {
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
                showAlert(`User "${data.full_name}" was saved successfully.`, 'success');
                loadUsers();
            } else {
                modalAlert.textContent = resp.error || 'Failed to save user.';
                modalAlert.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnText;
            modalAlert.textContent = 'Network error while saving user.';
            modalAlert.classList.remove('d-none');
        });
    }
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
