<?php
// modules/head/modify_records.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user = getCurrentUser();
$head_id = $user['user_id'];

// Get Head's Dept Courses
$courses_stmt = $mysqli->prepare("
    SELECT c.id, c.code, c.name 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    WHERE d.head_id = ?
    ORDER BY c.code ASC
");
$courses_stmt->bind_param("i", $head_id);
$courses_stmt->execute();
$result = $courses_stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$courses_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Modify Records - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Modify Records</h2>
            <a href="/CourseMonitor/modules/head/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm" onsubmit="loadRecords(event)">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Course</label>
                            <select id="course_id" class="form-select" onchange="loadAssessmentTypes(); loadRecords();" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Record Type</label>
                            <select id="record_type" class="form-select" onchange="handleTypeChange()" required>
                                <option value="attendance">Attendance</option>
                                <option value="coursework">Coursework</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="assessmentFilterDiv" style="display:none;">
                            <label class="form-label">Assessment Type</label>
                            <select id="assessment_filter" class="form-select" onchange="loadRecords()">
                                <option value="">All Types</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <!-- Hidden submit button to keep form semantics if needed, or just relying on change events -->
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>ID</th>
                                <th>Date / Type</th>
                                <th>Current Value</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recordsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editId">
                    <input type="hidden" id="editType">
                    
                    <div class="mb-3" id="valueInputContainer">
                        <!-- Dynamic -->
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Modification <span class="text-danger">*</span></label>
                        <textarea id="editReason" class="form-control" rows="3" required></textarea>
                        <div class="form-text">Required for audit trail.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveModification()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editModal;

        document.addEventListener('DOMContentLoaded', () => {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
        });

        function handleTypeChange() {
            const type = document.getElementById('record_type').value;
            const assessDiv = document.getElementById('assessmentFilterDiv');
            
            if (type === 'attendance') {
                assessDiv.style.display = 'none';
            } else {
                assessDiv.style.display = 'block';
                loadAssessmentTypes();
            }
            loadRecords(); // Trigger load immediately
        }

        function loadAssessmentTypes() {
            const courseId = document.getElementById('course_id').value;
            const select = document.getElementById('assessment_filter');
            
            // Clear but keep "Select Type"
            select.innerHTML = '<option value="">Select Type</option>';
            
            if (!courseId) return;

            fetch(`/CourseMonitor/api/get_assessment_types.php?course_id=${courseId}`)
                .then(r => r.json())
                .then(types => {
                    types.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t;
                        opt.textContent = t;
                        select.appendChild(opt);
                    });
                });
        }

        function loadRecords(event) {
            if(event) event.preventDefault();
            const courseId = document.getElementById('course_id').value;
            const type = document.getElementById('record_type').value;
            let filterVal = '';

            // Only use filterVal for Coursework (Assessment Type)
            // For Attendance, we send empty filterVal which means "All Dates"
            if (type === 'coursework') {
                filterVal = document.getElementById('assessment_filter').value;
            }

            if (!courseId) return;

            let url = `/CourseMonitor/api/search_records.php?course_id=${courseId}&type=${type}`;
            if (filterVal) url += `&filter_value=${encodeURIComponent(filterVal)}`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    const tbody = document.getElementById('recordsBody');
                    tbody.innerHTML = '';
                    if (data.error) {
                        // Don't alert if just empty
                        console.error(data.error);
                        return;
                    }
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>';
                        return;
                    }

                    data.forEach(row => {
                        let valDisplay = row.value;
                        let context = type === 'attendance' ? row.date : row.assessment_type;
                        
                        tbody.innerHTML += `
                            <tr>
                                <td>${row.student_name}</td>
                                <td>${row.student_id_number}</td>
                                <td>${context}</td>
                                <td>${valDisplay}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                        onclick='openEdit(${JSON.stringify(row)})'>Edit</button>
                                </td>
                            </tr>
                        `;
                    });
                });
        }

        function openEdit(row) {
            document.getElementById('editId').value = row.id;
            document.getElementById('editType').value = document.getElementById('record_type').value; 
            document.getElementById('editReason').value = '';

            const container = document.getElementById('valueInputContainer');
            const type = document.getElementById('record_type').value;

            if (type === 'attendance') {
                container.innerHTML = `
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" id="editDate" class="form-control" value="${row.date}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select id="newValue" class="form-select">
                            <option value="Present">Present</option>
                            <option value="Absent">Absent</option>
                            <option value="Late">Late</option>
                        </select>
                    </div>
                `;
                setTimeout(() => document.getElementById('newValue').value = row.value, 0);
            } else {
                container.innerHTML = `
                    <label class="form-label">Grade</label>
                    <input type="number" id="newValue" class="form-control" step="0.01" min="0" max="100" value="${row.value}">
                `;
            }
            
            editModal.show();
        }

        function saveModification() {
            const id = document.getElementById('editId').value;
            const type = document.getElementById('editType').value; 
            const newValue = document.getElementById('newValue').value;
            const reason = document.getElementById('editReason').value;
            
            let newDate = null;
            if (type === 'attendance') {
                newDate = document.getElementById('editDate').value;
            }

            if (!reason) {
                alert("Please provide a reason for this modification.");
                return;
            }

            fetch('/CourseMonitor/api/update_record_audit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    type: type,
                    new_value: newValue,
                    new_date: newDate,
                    reason: reason,
                    csrf_token: '<?php echo generateCsrfToken(); ?>'
                })
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    editModal.hide();
                    document.getElementById('filterForm').dispatchEvent(new Event('submit'));
                } else {
                    alert(resp.error);
                }
            });
        }
    </script>
</body>
</html>