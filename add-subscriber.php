<?php
require 'db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: index.php");
    exit;
}

if ($_POST) {
    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $email_address = trim($_POST['email_address'] ?? '');
    $list_id       = (int)($_POST['list_id'] ?? 0);

    if (!$first_name || !$email_address || $list_id <= 0 || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        header("Location: dashboard.php?error=invalid");
        exit;
    }

    $default_password = '12345';
    $password_hash = password_hash($default_password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Users_List 
            (first_name, last_name, email_address, signup_date, status, list_id, password_hash)
            VALUES (?, ?, ?, CURDATE(), 'Active', ?, ?)
            ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                list_id = VALUES(list_id),
                status = 'Active',
                password_hash = VALUES(password_hash)
        ");
        $stmt->execute([$first_name, $last_name, $email_address, $list_id, $password_hash]);

        header("Location: dashboard.php?msg=added");
        exit;
    } catch (Exception $e) {
        header("Location: dashboard.php?error=exists");
        exit;
    }
}
?>


