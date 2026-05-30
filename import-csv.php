<?php
require 'db.php';
if(!isset($_SESSION['employee_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

if(!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0){
    echo json_encode(['success'=>false,'message'=>'No file uploaded']);
    exit;
}

$csv = array_map('str_getcsv', file($_FILES['csv_file']['tmp_name']));
$imported = 0;

foreach($csv as $row){
    $first = trim($row[0] ?? '');
    $last  = trim($row[1] ?? '');
    $email = trim($row[2] ?? '');
    $list  = (int)($row[3] ?? 1); // Default list id = 1

    if($first && filter_var($email, FILTER_VALIDATE_EMAIL)){
        $default_pass = password_hash('12345', PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Users_List 
                (first_name, last_name, email_address, signup_date, status, list_id, password_hash)
                VALUES (?, ?, ?, CURDATE(), 'Active', ?, ?)
                ON DUPLICATE KEY UPDATE
                    first_name=VALUES(first_name),
                    last_name=VALUES(last_name),
                    list_id=VALUES(list_id),
                    status='Active',
                    password_hash=VALUES(password_hash)
            ");
            $stmt->execute([$first,$last,$email,$list,$default_pass]);
            $imported++;
        } catch(Exception $e){
            continue; // تخطي الأخطاء
        }
    }
}

echo json_encode(['success'=>true, 'imported'=>$imported]);
