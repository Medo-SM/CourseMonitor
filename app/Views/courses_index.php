<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Courses</title>
</head>
<body>

<h1>My Courses</h1>

<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Code</th>
        <th>Name</th>
    </tr>

    <?php foreach ($courses as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['id']) ?></td>
            <td><?= htmlspecialchars($c['code']) ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
        </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
