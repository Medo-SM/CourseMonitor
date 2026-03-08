<?php
// modules/lecturer/view_attendance.php
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
    <title>View Attendance - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; padding: 2rem; background-color: #f8f9fa; }
        .table-responsive { max-height: 70vh; }
        th { position: sticky; top: 0; background: white; z-index: 10; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1); white-space: nowrap; }
        .status-P { color: green; font-weight: bold; }
        .status-A { color: red; font-weight: bold; }
        .status-L { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Course Attendance Log</h2>
            <a href="/CourseMonitor/modules/lecturer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Select Course</label>
                        <select id="course" class="form-select" onchange="loadAttendance()">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $c['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" id="from_date" class="form-control" onchange="loadAttendance()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" id="to_date" class="form-control" onchange="loadAttendance()">
                    </div>
                    <div class="col-md-2 text-end">
                        <button onclick="window.print()" class="btn btn-outline-dark w-100">Print</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="attendanceTable">
                        <thead class="table-light">
                            <!-- Dynamic Headers -->
                        </thead>
                        <tbody>
                            <tr><td class="p-3 text-center text-muted">Select a course to view attendance.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('course').value) loadAttendance();
        });

        function loadAttendance() {
            const courseId = document.getElementById('course').value;
            const fromDate = document.getElementById('from_date').value;
            const toDate = document.getElementById('to_date').value;
            
            const table = document.getElementById('attendanceTable');
            const thead = table.querySelector('thead');
            const tbody = table.querySelector('tbody');

            if (!courseId) return;

            tbody.innerHTML = '<tr><td colspan="100" class="text-center p-3"><div class="spinner-border text-primary"></div></td></tr>';

            let url = `/CourseMonitor/api/get_attendance_matrix.php?course_id=${courseId}`;
            if (fromDate) url += `&from_date=${fromDate}`;
            if (toDate) url += `&to_date=${toDate}`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="100" class="text-center p-3 text-danger">${data.error}</td></tr>`;
                        return;
                    }

                    // 1. Build Headers
                    let headerHtml = '<tr><th>Student ID</th><th>Name</th>';
                    data.dates.forEach(d => {
                        headerHtml += `<th class="text-center">${d}</th>`;
                    });
                    headerHtml += '<th class="text-center">P</th><th class="text-center">A</th><th class="text-center">L</th><th class="text-center">Rate</th></tr>';
                    thead.innerHTML = headerHtml;

                    // 2. Build Rows
                    tbody.innerHTML = '';
                    if (data.students.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="${data.dates.length + 6}" class="text-center p-3">No students found.</td></tr>`;
                        return;
                    }

                    data.students.forEach(student => {
                        let rowHtml = `<tr>
                            <td>${student.student_id_number}</td>
                            <td>${student.name}</td>`;
                        
                        let pCount = 0, aCount = 0, lCount = 0;
                        let totalRecorded = 0;
                        const studentAtt = data.attendance[student.id] || {};

                        data.dates.forEach(d => {
                            const status = studentAtt[d];
                            let cell = '-';
                            if (status) {
                                totalRecorded++;
                                const short = status.charAt(0);
                                const className = `status-${short}`;
                                cell = `<span class="${className}">${short}</span>`;
                                
                                if (short === 'P') pCount++;
                                else if (short === 'A') aCount++;
                                else if (short === 'L') lCount++;
                            }
                            rowHtml += `<td class="text-center">${cell}</td>`;
                        });

                        // Calculate Rate
                        const rate = totalRecorded > 0 ? ((pCount / totalRecorded) * 100).toFixed(0) + '%' : '-';
                        
                        rowHtml += `<td class="text-center fw-bold text-success">${pCount}</td>`;
                        rowHtml += `<td class="text-center fw-bold text-danger">${aCount}</td>`;
                        rowHtml += `<td class="text-center fw-bold text-warning">${lCount}</td>`;
                        rowHtml += `<td class="text-center fw-bold">${rate}</td></tr>`;
                        
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