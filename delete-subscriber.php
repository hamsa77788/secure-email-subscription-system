<?php
require 'db.php';

if(!isset($_SESSION['employee_id']) || !isset($_GET['id']) || !isset($_GET['list'])) {
    header("Location: dashboard.php");
    exit;
}

$email_id = (int)$_GET['id'];
$list_id  = (int)$_GET['list'];

$stmt = $pdo->prepare("DELETE FROM Users_List WHERE email_id = ? AND list_id = ?");
$stmt->execute([$email_id, $list_id]);

header("Location: dashboard.php?msg=deleted");
exit;
