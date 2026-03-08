<?php
// api/get_faculties.php
require_once __DIR__ . '/../includes/auth_functions.php';
// Allow public for signup if needed, or restricting to admin/head? 
// Signup needs it. So let's just make it public or check if logged in for management.
// For signup, we might need a separate public endpoint or just allow this one.
// The auth_functions might redirect if not logged in.
// Let's create a public version or modify this.
// For now, let's assume this is for admin panel. Signup will need a way to fetch faculties.

// If we want to use this in signup.php (public), we shouldn't requireRole('admin').
// Let's make it open if no role is enforced, or create a specific one.
// Actually, `signup.php` is a PHP file that renders HTML. It can fetch data internally if it includes db.php.
// But if we use AJAX on signup page, we need a public API.

// Let's check `auth_functions.php`. requireRole redirects if not logged in.
// So we can't use this for public signup AJAX.

if (isset($_GET['public'])) {
    require_once __DIR__ . '/../config/db.php';
} else {
    require_once __DIR__ . '/../includes/auth_functions.php';
    requireRole(['admin', 'head', 'lecturer']); 
}

header('Content-Type: application/json');

global $mysqli;

$stmt = $mysqli->prepare("SELECT id, name FROM faculties ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();

$faculties = [];
while ($row = $result->fetch_assoc()) {
    $faculties[] = $row;
}

echo json_encode($faculties);
?>