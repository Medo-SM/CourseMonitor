<?php
// modules/head/reports.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');

$user_id = $_SESSION['user_id'];

// Fetch courses for filter
// Only courses in Dept managed by THIS Head
$courses_stmt = $mysqli->prepare("
    SELECT c.id, c.code, c.name 
    FROM courses c 
    JOIN departments d ON c.department_id = d.id 
    WHERE d.head_id = ?
    ORDER BY c.code ASC
");
$courses_stmt->bind_param("i", $user_id);
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
    <title>Department Reports - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .big-stat { font-size: 2.5rem; font-weight: bold; }
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000; box-shadow: none; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2>Department Reports</h2>
            <a href="/CourseMonitor/modules/head/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Filter by Course</label>
                        <select id="courseFilter" class="form-select">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button onclick="loadReports()" class="btn btn-primary">Generate Report</button>
                        <button onclick="window.print()" class="btn btn-outline-dark">Print / Save PDF</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="reportContainer" style="display:none;">
            <div class="text-center mb-4">
                <h3 id="reportTitle">Department Performance Report</h3>
                <p class="text-muted">Generated on: <span id="reportDate"></span></p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card stat-card border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Average Grade</h5>
                            <div class="big-stat" id="avgGrade">--</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card border-success h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success">Average Attendance</h5>
                            <div class="big-stat" id="attPercent">--%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Grade Distribution</div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Grade Range</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody id="gradeDistBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">At-Risk Students (Attendance < 75%)</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Att. %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="riskBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadReports);

        function loadReports() {
            const courseId = document.getElementById('courseFilter').value;
            const container = document.getElementById('reportContainer');
            
            // Show loading or something if needed
            
            let url = '/CourseMonitor/api/get_reports.php';
            if (courseId) {
                url += `?course_id=${courseId}`;
                document.getElementById('reportTitle').innerText = 'Course Performance Report';
            } else {
                document.getElementById('reportTitle').innerText = 'Department Performance Report';
            }

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('reportDate').innerText = new Date().toLocaleString();
                    document.getElementById('avgGrade').innerText = data.avg_grade;
                    document.getElementById('attPercent').innerText = data.attendance_percent + '%';

                    // Grade Dist
                    const distBody = document.getElementById('gradeDistBody');
                    distBody.innerHTML = '';
                    const labels = {
                        'A': '90-100 (A)',
                        'B': '80-89 (B)',
                        'C': '70-79 (C)',
                        'D': '60-69 (D)',
                        'F': '0-59 (F)'
                    };
                    for (const [key, val] of Object.entries(data.grade_distribution)) {
                        distBody.innerHTML += `
                            <tr>
                                <td>${labels[key] || key}</td>
                                <td>${val}</td>
                            </tr>
                        `;
                    }

                    // Risk Table
                    const riskBody = document.getElementById('riskBody');
                    riskBody.innerHTML = '';
                    if (data.at_risk.length === 0) {
                        riskBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No students at risk.</td></tr>';
                    } else {
                        data.at_risk.forEach(row => {
                            riskBody.innerHTML += `
                                <tr>
                                    <td>${row.student_name}</td>
                                    <td>${row.course_code}</td>
                                    <td class="text-danger fw-bold">${parseFloat(row.att_pct).toFixed(1)}%</td>
                                </tr>
                            `;
                        });
                    }

                    container.style.display = 'block';
                })
                .catch(e => {
                    console.error(e);
                    alert("Failed to load report data.");
                });
        }
    </script>
</body>
</html>