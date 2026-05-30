<?php
// export-csv.php → FINAL VERSION (works 100%)
require 'db.php';

// Make sure user is logged in (silent check)
if (!isset($_SESSION['employee_id'])) {
    die('Please login first');
}

// === CRITICAL: NO OUTPUT BEFORE THESE HEADERS ===
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// CSV Header row
fputcsv($output, [
    'First Name',
    'Last Name',
    'Email',
    'Signup Date',
    'Status',
    'List Name'
]);

// Fetch data
$stmt = $pdo->query("
    SELECT 
        u.first_name,
        u.last_name,
        u.email_address,
        u.signup_date,
        u.status,
        COALESCE(l.list_name, 'No List') AS list_name
    FROM Users_List u
    LEFT JOIN Email_List l ON u.list_id = l.list_id
    ORDER BY u.signup_date DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['first_name'],
        $row['last_name'],
        $row['email_address'],
        $row['signup_date'],
        $row['status'],
        $row['list_name']
    ]);
}

// Done – close everything
fclose($output);
exit;