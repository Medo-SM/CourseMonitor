<?php
// modules/admin/faculties.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Management - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Faculty Management</h2>
            <a href="/CourseMonitor/modules/admin/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <button onclick="openModal()" class="btn btn-success mb-3">Add Faculty</button>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="facultyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="facultyForm" onsubmit="saveFaculty(event)">
                        <input type="hidden" id="id">
                        <div class="mb-3">
                            <label class="form-label">Faculty Name</label>
                            <input type="text" id="name" class="form-control" required>
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
            modal = new bootstrap.Modal(document.getElementById('facultyModal'));
            loadFaculties();
        });

        function loadFaculties() {
            fetch('/CourseMonitor/api/get_faculties.php')
                .then(r => r.json())
                .then(list => {
                    const tbody = document.getElementById('facultyTableBody');
                    tbody.innerHTML = '';
                    list.forEach(item => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${item.name}</td>
                                <td>
                                    <button onclick='editFaculty(${JSON.stringify(item)})' class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button onclick='deleteFaculty(${item.id})' class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function openModal() {
            document.getElementById('facultyForm').reset();
            document.getElementById('id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Faculty';
            modal.show();
        }

        function editFaculty(item) {
            document.getElementById('modalTitle').innerText = 'Edit Faculty';
            document.getElementById('id').value = item.id;
            document.getElementById('name').value = item.name;
            modal.show();
        }

        function saveFaculty(event) {
            event.preventDefault();
            const id = document.getElementById('id').value;
            
            const data = {
                id: id,
                name: document.getElementById('name').value,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            };

            fetch('/CourseMonitor/api/save_faculty.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    modal.hide();
                    loadFaculties();
                } else {
                    alert(resp.error);
                }
            });
        }

        function deleteFaculty(id) {
            if(!confirm('Are you sure? This may affect linked departments and users!')) return;

            fetch('/CourseMonitor/api/delete_faculty.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, csrf_token: '<?php echo generateCsrfToken(); ?>' })
            })
            .then(r => r.json())
            .then(resp => {
                if(resp.success) {
                    loadFaculties();
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>