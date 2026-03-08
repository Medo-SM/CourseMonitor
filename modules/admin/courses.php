<?php
// modules/admin/courses.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Management - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .semester-checkboxes { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Course Management</h2>
            <a href="/CourseMonitor/modules/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <select id="filter_dept_id" class="form-select" onchange="loadCourses()">
                            <option value="">Select Department to View Courses</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search courses..." onkeyup="loadCourses()">
                    </div>
                    <div class="col-md-4 text-end">
                        <button onclick="openModal()" class="btn btn-success text-nowrap">Add Course</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Lecturer</th>
                                <th>Semesters</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="courseTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="courseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="courseForm" onsubmit="saveCourse(event)">
                        <input type="hidden" id="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Code</label>
                                <input type="text" id="code" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Name</label>
                                <input type="text" id="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Credit Hours</label>
                                <input type="number" id="credit_hours" class="form-control" required min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select id="department_id" class="form-select" required>
                                    <option value="">Select Department</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Lecturer</label>
                            <select id="lecturer_id" class="form-select">
                                <option value="">Select Lecturer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recommended Semesters</label>
                            <div class="semester-checkboxes p-2 border rounded bg-white">
                                <?php for($i=1; $i<=8; $i++): ?>
                                    <div class="form-check">
                                        <input class="form-check-input sem-check" type="checkbox" value="<?php echo $i; ?>" id="sem<?php echo $i; ?>">
                                        <label class="form-check-label" for="sem<?php echo $i; ?>">Sem <?php echo $i; ?></label>
                                    </div>
                                <?php endfor; ?>
                            </div>
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
            modal = new bootstrap.Modal(document.getElementById('courseModal'));
            loadDepartments();
            loadLecturers();
            // Don't load courses initially, wait for selection
            document.getElementById('courseTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-muted">Please select a department to view courses.</td></tr>';
        });

        function loadDepartments() {
            fetch('/CourseMonitor/api/get_departments.php')
                .then(r => r.json())
                .then(depts => {
                    // Populate Filter
                    const filterSelect = document.getElementById('filter_dept_id');
                    // Populate Form Select
                    const formSelect = document.getElementById('department_id');
                    
                    depts.forEach(d => {
                        const opt1 = document.createElement('option');
                        opt1.value = d.id;
                        opt1.textContent = d.name;
                        filterSelect.appendChild(opt1);

                        const opt2 = document.createElement('option');
                        opt2.value = d.id;
                        opt2.textContent = d.name;
                        formSelect.appendChild(opt2);
                    });
                });
        }

        function loadLecturers() {
            fetch('/CourseMonitor/api/get_lecturers.php')
                .then(r => r.json())
                .then(lecturers => {
                    const select = document.getElementById('lecturer_id');
                    select.innerHTML = '<option value="">Select Lecturer</option>';
                    lecturers.forEach(l => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.full_name;
                        select.appendChild(opt);
                    });
                });
        }

        function loadCourses() {
            const deptId = document.getElementById('filter_dept_id').value;
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('courseTableBody');

            if (!deptId) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Please select a department to view courses.</td></tr>';
                return;
            }

            // Fetch with dept filter
            let url = `/CourseMonitor/api/get_courses.php?department_id=${deptId}`;
            
            fetch(url)
                .then(r => r.json())
                .then(courses => {
                    tbody.innerHTML = '';
                    
                    // Client-side search filtering if needed, or simple display
                    const filtered = courses.filter(c => {
                        if (!search) return true;
                        const term = search.toLowerCase();
                        return c.code.toLowerCase().includes(term) || c.name.toLowerCase().includes(term);
                    });

                    if (filtered.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No courses found in this department.</td></tr>';
                        return;
                    }

                    filtered.forEach(c => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${c.code}</td>
                                <td>${c.name}</td>
                                <td>${c.department_name || '-'}</td>
                                <td>${c.lecturer_name || '<span class="text-muted">Unassigned</span>'}</td>
                                <td>${c.semesters.join(', ') || '-'}</td>
                                <td>
                                    <button onclick='editCourse(${JSON.stringify(c)})' class="btn btn-sm btn-outline-primary">Edit</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function openModal() {
            document.getElementById('courseForm').reset();
            document.getElementById('id').value = '';
            document.querySelectorAll('.sem-check').forEach(c => c.checked = false);
            document.getElementById('modalTitle').innerText = 'Add Course';
            
            // Auto-select department in modal if filtered
            const currentFilter = document.getElementById('filter_dept_id').value;
            if(currentFilter) {
                document.getElementById('department_id').value = currentFilter;
            }
            
            modal.show();
        }

        function editCourse(course) {
            document.getElementById('modalTitle').innerText = 'Edit Course';
            document.getElementById('id').value = course.id;
            document.getElementById('code').value = course.code;
            document.getElementById('name').value = course.name;
            document.getElementById('credit_hours').value = course.credit_hours;
            document.getElementById('department_id').value = course.department_id;
            document.getElementById('lecturer_id').value = course.lecturer_id || '';
            
            document.querySelectorAll('.sem-check').forEach(c => {
                c.checked = course.semesters.includes(parseInt(c.value));
            });

            modal.show();
        }

        function saveCourse(event) {
            event.preventDefault();
            const id = document.getElementById('id').value;
            const semesters = Array.from(document.querySelectorAll('.sem-check:checked')).map(c => parseInt(c.value));
            
            const data = {
                id: id,
                code: document.getElementById('code').value,
                name: document.getElementById('name').value,
                credit_hours: document.getElementById('credit_hours').value,
                department_id: document.getElementById('department_id').value,
                lecturer_id: document.getElementById('lecturer_id').value,
                semesters: semesters,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            };

            fetch('/CourseMonitor/api/save_course.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    modal.hide();
                    loadCourses(); // Refresh list
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>