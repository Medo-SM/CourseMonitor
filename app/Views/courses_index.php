<?php
// Prevent warnings if the view is loaded without $courses for any reason
$courses = $courses ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Courses</title>
</head>
<body>

<h1>My Courses</h1>

<?php if (count($courses) === 0): ?>
    <p>No courses found.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Name</th>
            <th>Semester</th>
            <th>Academic Year</th>
        </tr>

        <?php foreach ($courses as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['id'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['code'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['semester'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['academic_year'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>

    </table>
<?php endif; ?>

</body>
</html>
