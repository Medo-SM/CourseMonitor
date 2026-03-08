<?php
// modules/head/audit_logs.php
require_once __DIR__ . '/../../includes/auth_functions.php';
requireRole('head');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - CourseMonitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .old-val { text-decoration: line-through; color: #dc3545; }
        .new-val { font-weight: bold; color: #198754; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Modification History</h2>
            <a href="/CourseMonitor/modules/head/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Change</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                            <tr><td colspan="5" class="text-center"><div class="spinner-border text-primary"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', loadLogs);

        function loadLogs() {
            fetch('/CourseMonitor/api/get_audit_logs.php')
                .then(r => r.json())
                .then(data => {
                    const tbody = document.getElementById('logTableBody');
                    tbody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No modifications found.</td></tr>';
                        return;
                    }

                    data.forEach(row => {
                        // Extract Reason
                        let reason = row.action;
                        if (row.action.includes('(Reason:')) {
                            reason = row.action.substring(row.action.indexOf('(Reason:') + 9, row.action.length - 1).trim();
                        }

                        tbody.innerHTML += `
                            <tr>
                                <td class="text-muted small">${new Date(row.modified_at).toLocaleString()}</td>
                                <td><span class="badge bg-secondary">${row.table_name}</span></td>
                                <td>${row.details}</td>
                                <td>
                                    <span class="old-val">${row.old_value || 'N/A'}</span>
                                    <i class="bi bi-arrow-right mx-1">→</i>
                                    <span class="new-val">${row.new_value}</span>
                                </td>
                                <td>${reason}</td>
                            </tr>
                        `;
                    });
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('logTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading logs.</td></tr>';
                });
        }
    </script>
</body>
</html>