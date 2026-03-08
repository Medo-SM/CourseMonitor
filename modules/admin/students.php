<?php
// modules/admin/students.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Student Management</h2>
            <a href="/CourseMonitor/modules/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search students..." onkeyup="loadStudents()">
                    <button onclick="openModal()" class="btn btn-success text-nowrap">Add Student</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Semester</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="studentForm" onsubmit="saveStudent(event)">
                        <input type="hidden" id="id">
                        <div class="mb-3">
                            <label class="form-label">Student ID Number</label>
                            <input type="text" id="student_id_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" class="form-control" required>
                        </div>
                         <div class="mb-3">
                            <label class="form-label">Current Semester (1-8)</label>
                            <input type="number" id="current_semester" class="form-control" min="1" max="12" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select id="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                            </select>
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
            modal = new bootstrap.Modal(document.getElementById('studentModal'));
            loadDepartments();
            loadStudents();
        });

        function loadDepartments() {
            fetch('/CourseMonitor/api/get_departments.php')
                .then(r => r.json())
                .then(depts => {
                    const select = document.getElementById('department_id');
                    depts.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.name;
                        select.appendChild(opt);
                    });
                });
        }

        function loadStudents() {
            const search = document.getElementById('searchInput').value;
            fetch(`/CourseMonitor/api/get_students_manage.php?search=${search}`)
                .then(r => r.json())
                .then(students => {
                    const tbody = document.getElementById('studentTableBody');
                    tbody.innerHTML = '';
                    students.forEach(s => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${s.student_id_number}</td>
                                <td>${s.name}</td>
                                <td>${s.email}</td>
                                <td>${s.current_semester || '1'}</td>
                                <td>${s.department_name || 'N/A'}</td>
                                <td>
                                    <button onclick='editStudent(${JSON.stringify(s)})' class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button onclick='deleteStudent(${s.id})' class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function openModal() {
            document.getElementById('studentForm').reset();
            document.getElementById('id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Student';
            modal.show();
        }

        function editStudent(student) {
            document.getElementById('modalTitle').innerText = 'Edit Student';
            document.getElementById('id').value = student.id;
            document.getElementById('student_id_number').value = student.student_id_number;
            document.getElementById('name').value = student.name;
            document.getElementById('email').value = student.email;
            document.getElementById('current_semester').value = student.current_semester || 1;
            document.getElementById('department_id').value = student.department_id;
            modal.show();
        }

        function deleteStudent(id) {
            if (!confirm('Are you sure you want to delete this student? All their records (grades, attendance) will be permanently deleted.')) return;

            fetch('/CourseMonitor/api/delete_student.php', {
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
                    alert('Student deleted successfully.');
                    loadStudents();
                } else {
                    alert('Error: ' + resp.error);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error during deletion.');
            });
        }

        function saveStudent(event) {
            event.preventDefault();
            const id = document.getElementById('id').value;
            
            const data = {
                id: id,
                student_id_number: document.getElementById('student_id_number').value,
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                current_semester: document.getElementById('current_semester').value,
                department_id: document.getElementById('department_id').value,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            };

            fetch('/CourseMonitor/api/save_student.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    modal.hide();
                    loadStudents();
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>