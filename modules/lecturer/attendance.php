<?php
// modules/lecturer/attendance.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('lecturer');

$lecturer_id = $_SESSION['user_id'];

// Fetch lecturer's courses
$courses_stmt = $mysqli->prepare("SELECT id, code, name FROM courses WHERE lecturer_id = ?");
if (!$courses_stmt) {
    die("Error preparing statement: " . $mysqli->error);
}
$courses_stmt->bind_param("i", $lecturer_id);
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
    <title>Record Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Record Attendance</h2>
            <a href="/CourseMonitor/modules/lecturer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Course</label>
                        <select id="course" class="form-select" onchange="loadStudents()">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" id="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <button onclick="markAllPresent()" class="btn btn-outline-success w-100">Mark All Present</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form id="attendanceForm" onsubmit="saveAttendance(event)">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="studentTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center text-muted">Select a course to load students.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
             const courseSelect = document.getElementById('course');
             if (courseSelect.value) {
                 loadStudents();
             }
        });

        function loadStudents() {
            const courseId = document.getElementById('course').value;
            const tbody = document.querySelector('#studentTable tbody');
            
            if (!courseId) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Select a course.</td></tr>';
                return;
            }

            tbody.innerHTML = '<tr><td colspan="3" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>';

            fetch(`/CourseMonitor/api/get_students.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${data.error}</td></tr>`;
                        return;
                    }
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No students found.</td></tr>';
                        return;
                    }

                    data.forEach(student => {
                        const row = `
                            <tr>
                                <td>${student.student_id_number}</td>
                                <td>${student.name}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="status[${student.id}]" id="present_${student.id}" value="Present" required>
                                        <label class="btn btn-outline-success" for="present_${student.id}">Present</label>

                                        <input type="radio" class="btn-check" name="status[${student.id}]" id="absent_${student.id}" value="Absent">
                                        <label class="btn btn-outline-danger" for="absent_${student.id}">Absent</label>

                                        <input type="radio" class="btn-check" name="status[${student.id}]" id="late_${student.id}" value="Late">
                                        <label class="btn btn-outline-warning" for="late_${student.id}">Late</label>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Network Error</td></tr>`;
                });
        }

        function markAllPresent() {
            const radios = document.querySelectorAll('input[value="Present"]');
            radios.forEach(radio => radio.checked = true);
        }

        function saveAttendance(event) {
            event.preventDefault();
            const courseId = document.getElementById('course').value;
            const dateStr = document.getElementById('date').value;
            
            if (!courseId || !dateStr) {
                alert("Please select a course and date.");
                return;
            }

            const formData = new FormData(event.target);
            const attendanceData = [];
            let missing = false;
            
            // Check for missing statuses manually since required attribute might not catch all in dynamic table if logic differs
            // but HTML5 required should handle it for radios sharing same name.
            
            // Collect data
            // We need to iterate over all students in the table to ensure we have a status for each?
            // The FormData will only contain checked values. 
            // Better to iterate the rows or rely on FormData.
            
            for (let [key, value] of formData.entries()) {
                const match = key.match(/status\[(\d+)\]/);
                if (match) {
                    const studentId = match[1];
                    attendanceData.push({ student_id: studentId, status: value });
                }
            }
            
            if (attendanceData.length === 0) {
                alert("No student data to save.");
                return;
            }

            fetch('/CourseMonitor/api/save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    course_id: courseId,
                    date: dateStr,
                    attendance: attendanceData,
                    csrf_token: '<?php echo generateCsrfToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Attendance saved successfully!');
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error');
            });
        }
    </script>
</body>
</html>