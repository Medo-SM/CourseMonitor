<?php
// modules/admin/departments.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Management - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Department Management</h2>
            <a href="/CourseMonitor/modules/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <button onclick="openModal()" class="btn btn-success mb-3">Add Department</button>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Faculty</th>
                                <th>Head of Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="deptTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="deptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="deptForm" onsubmit="saveDepartment(event)">
                        <input type="hidden" id="id">
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Faculty</label>
                            <select id="faculty_id" class="form-select">
                                <option value="">Select Faculty</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Head (Optional)</label>
                            <select id="head_id" class="form-select">
                                <option value="">Select Head</option>
                            </select>
                            <small class="text-muted">Only users with 'Department Head' role are listed.</small>
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
            modal = new bootstrap.Modal(document.getElementById('deptModal'));
            loadDepartments();
            loadHeads();
            loadFaculties();
        });

        function loadDepartments() {
            fetch('/CourseMonitor/api/get_department_details.php')
                .then(r => r.json())
                .then(depts => {
                    const tbody = document.getElementById('deptTableBody');
                    tbody.innerHTML = '';
                    depts.forEach(d => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${d.name}</td>
                                <td>${d.faculty_name || '<span class="text-muted">-</span>'}</td>
                                <td>${d.head_name || '<span class="text-muted">Not Assigned</span>'}</td>
                                <td>
                                    <button onclick='editDept(${JSON.stringify(d)})' class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button onclick='deleteDept(${d.id})' class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function loadHeads() {
            fetch('/CourseMonitor/api/get_users.php')
                .then(r => r.json())
                .then(users => {
                    const select = document.getElementById('head_id');
                    select.innerHTML = '<option value="">Select Head</option>';
                    users.filter(u => u.role === 'head').forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.full_name;
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

        function openModal() {
            document.getElementById('deptForm').reset();
            document.getElementById('id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Department';
            modal.show();
        }

        function editDept(dept) {
            document.getElementById('modalTitle').innerText = 'Edit Department';
            document.getElementById('id').value = dept.id;
            document.getElementById('name').value = dept.name;
            document.getElementById('head_id').value = dept.head_id || '';
            document.getElementById('faculty_id').value = dept.faculty_id || '';
            modal.show();
        }

        function saveDepartment(event) {
            event.preventDefault();
            const id = document.getElementById('id').value;
            
            const data = {
                id: id,
                name: document.getElementById('name').value,
                head_id: document.getElementById('head_id').value,
                faculty_id: document.getElementById('faculty_id').value,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            };

            fetch('/CourseMonitor/api/save_department.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    modal.hide();
                    loadDepartments();
                } else {
                    alert(resp.error);
                }
            });
        }

        function deleteDept(id) {
            if(!confirm('Are you sure? This will delete the department and unassign students/courses!')) return;

            fetch('/CourseMonitor/api/delete_department.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, csrf_token: '<?php echo generateCsrfToken(); ?>' })
            })
            .then(r => r.json())
            .then(resp => {
                if(resp.success) {
                    loadDepartments();
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>