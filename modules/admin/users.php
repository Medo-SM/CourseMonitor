<?php
// modules/admin/users.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>User Management</h2>
            <a href="/CourseMonitor/modules/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search users..." onkeyup="loadUsers()">
                    <button onclick="openModal()" class="btn btn-success text-nowrap">Add User</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Affiliation</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" onsubmit="saveUser(event)">
                        <input type="hidden" id="userId">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="fullName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select id="role" class="form-select" required onchange="toggleAffiliation()">
                                <option value="lecturer">Lecturer</option>
                                <option value="head">Department Head</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3" id="deptGroup" style="display:none;">
                            <label class="form-label">Department</label>
                            <select id="department_id" class="form-select">
                                <option value="">Select Department</option>
                            </select>
                            <div class="form-text">Required for Department Heads.</div>
                        </div>
                        <div class="mb-3" id="facultyGroup" style="display:none;">
                            <label class="form-label">Faculty</label>
                            <select id="faculty_id" class="form-select">
                                <option value="">Select Faculty</option>
                            </select>
                            <div class="form-text">Required for Lecturers.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (Leave blank if unchanged)</label>
                            <input type="password" id="password" class="form-control">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" id="isActive" class="form-check-input" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let modal;
        
        document.addEventListener('DOMContentLoaded', () => {
            modal = new bootstrap.Modal(document.getElementById('userModal'));
            loadDepartments();
            loadFaculties();
            loadUsers();
        });

        function loadDepartments() {
            fetch('/CourseMonitor/api/get_departments.php')
                .then(r => r.json())
                .then(depts => {
                    const select = document.getElementById('department_id');
                    select.innerHTML = '<option value="">Select Department</option>';
                    depts.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        select.appendChild(opt);
                    });
                });
        }

        function loadFaculties() {
            fetch('/CourseMonitor/api/get_faculties.php')
                .then(r => r.json())
                .then(list => {
                    const select = document.getElementById('faculty_id');
                    select.innerHTML = '<option value="">Select Faculty</option>';
                    list.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.name;
                        select.appendChild(opt);
                    });
                });
        }

        function loadUsers() {
            const search = document.getElementById('searchInput').value;
            fetch(`/CourseMonitor/api/get_users.php?search=${search}`)
                .then(r => r.json())
                .then(users => {
                    const tbody = document.getElementById('userTableBody');
                    tbody.innerHTML = '';
                    users.forEach(user => {
                        let aff = '-';
                        if(user.role === 'head') aff = user.department_name || 'N/A';
                        if(user.role === 'lecturer') aff = user.faculty_name || 'N/A';

                        tbody.innerHTML += `
                            <tr>
                                <td>${user.username}</td>
                                <td>${user.full_name}</td>
                                <td>${user.email}</td>
                                <td><span class="badge bg-secondary">${user.role}</span></td>
                                <td>${aff}</td>
                                <td>${user.is_active ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>'}</td>
                                <td>
                                    <button onclick='editUser(${JSON.stringify(user)})' class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button onclick='deleteUser(${user.id})' class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function toggleAffiliation() {
            const role = document.getElementById('role').value;
            document.getElementById('deptGroup').style.display = role === 'head' ? 'block' : 'none';
            document.getElementById('facultyGroup').style.display = role === 'lecturer' ? 'block' : 'none';
        }

        function openModal() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('modalTitle').innerText = 'Add User';
            toggleAffiliation();
            modal.show();
        }

        function editUser(user) {
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('department_id').value = user.department_id || '';
            document.getElementById('faculty_id').value = user.faculty_id || '';
            document.getElementById('isActive').checked = user.is_active == 1;
            
            toggleAffiliation();
            modal.show();
        }

        function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;

            fetch('/CourseMonitor/api/delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    csrf_token: '<?php echo generateCsrfToken(); ?>'
                })
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    alert('User deleted successfully.');
                    loadUsers();
                } else {
                    alert('Error: ' + resp.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error during deletion.');
            });
        }

        function saveUser(event) {
            event.preventDefault();
            const id = document.getElementById('userId').value;
            
            const data = {
                id: id,
                username: document.getElementById('username').value,
                full_name: document.getElementById('fullName').value,
                email: document.getElementById('email').value,
                role: document.getElementById('role').value,
                department_id: document.getElementById('department_id').value,
                faculty_id: document.getElementById('faculty_id').value,
                password: document.getElementById('password').value,
                is_active: document.getElementById('isActive').checked ? 1 : 0,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            };

            fetch('/CourseMonitor/api/save_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    modal.hide();
                    loadUsers();
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>