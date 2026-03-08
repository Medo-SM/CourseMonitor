<?php
// modules/lecturer/view_grades.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

// Get Courses
$stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ?");
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$res = $stmt->get_result();
$courses = [];
while ($row = $res->fetch_assoc()) $courses[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Grades - Gradebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f8f9fa; }
        .table-responsive { max-height: 70vh; }
        th { position: sticky; top: 0; background: white; z-index: 10; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Course Gradebook</h2>
            <a href="/CourseMonitor/modules/lecturer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Select Course</label>
                        <select id="course" class="form-select" onchange="loadGradebook()">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8 text-end">
                        <button onclick="window.print()" class="btn btn-outline-dark">Print</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="gradebookTable">
                        <thead class="table-light">
                            <!-- Dynamic Headers -->
                        </thead>
                        <tbody>
                            <tr><td class="p-3 text-center text-muted">Select a course to view grades.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('course').value) loadGradebook();
        });

        function loadGradebook() {
            const courseId = document.getElementById('course').value;
            const table = document.getElementById('gradebookTable');
            const thead = table.querySelector('thead');
            const tbody = table.querySelector('tbody');

            if (!courseId) return;

            tbody.innerHTML = '<tr><td colspan="100" class="text-center p-3"><div class="spinner-border text-primary"></div></td></tr>';

            fetch(`/CourseMonitor/api/get_gradebook.php?course_id=${courseId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="100" class="text-center p-3 text-danger">${data.error}</td></tr>`;
                        return;
                    }

                    // 1. Build Headers
                    let headerHtml = '<tr><th>Student ID</th><th>Name</th>';
                    data.assessments.forEach(ass => {
                        headerHtml += `<th>${ass}</th>`;
                    });
                    headerHtml += '<th>Total</th></tr>';
                    thead.innerHTML = headerHtml;

                    // 2. Build Rows
                    tbody.innerHTML = '';
                    if (data.students.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="${data.assessments.length + 3}" class="text-center p-3">No students found.</td></tr>`;
                        return;
                    }

                    data.students.forEach(student => {
                        let rowHtml = `<tr>
                            <td>${student.student_id_number}</td>
                            <td>${student.name}</td>`;
                        
                        let total = 0;
                        const studentGrades = data.grades[student.id] || {};

                        data.assessments.forEach(ass => {
                            const grade = studentGrades[ass];
                            const display = grade !== undefined ? parseFloat(grade).toFixed(1) : '-';
                            rowHtml += `<td class="text-center">${display}</td>`;
                            if (grade !== undefined) total += parseFloat(grade);
                        });

                        // Simple Total (Sum of all columns) - Logic might need refinement based on weights, but Sum is standard for now
                        rowHtml += `<td class="text-center fw-bold">${total.toFixed(1)}</td></tr>`;
                        tbody.innerHTML += rowHtml;
                    });
                })
                .catch(e => {
                    console.error(e);
                    tbody.innerHTML = `<tr><td colspan="100" class="text-center p-3 text-danger">Network Error</td></tr>`;
                });
        }
    </script>
</body>
</html>