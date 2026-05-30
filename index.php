<?php
error_reporting(E_ALL & ~E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

if (isset($_SESSION['employee_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Reset messages
$login_error = '';
$sub_error   = '';
$sub_success = '';

// ====================
// EMPLOYEE LOGIN → password is plain text now
// ====================
if (isset($_POST['employee_login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        // Note: column name is now 'password' not 'password_hash'
        $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, password FROM Employee WHERE email = ?");
        $stmt->execute([$email]);
        $emp = $stmt->fetch();

        // Direct comparison because password is stored as plain text
        if ($emp && $password === $emp['password']) {
            $_SESSION['employee_id'] = $emp['employee_id'];
            $_SESSION['name'] = $emp['first_name'] . ' ' . $emp['last_name'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $login_error = "Invalid email or password!";
}

// ====================
// PUBLIC SUBSCRIPTION → password still hashed for users
// ====================
if (isset($_POST['public_subscribe'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email_address'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $list_id    = (int)($_POST['list_id'] ?? 0);

    if (!$first_name || !filter_var($email, FILTER_VALIDATE_EMAIL) || $list_id <= 0) {
        $sub_error = "Please fill all required fields correctly.";
    } elseif ($password !== $confirm) {
        $sub_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $sub_error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Users_List 
                (first_name, last_name, email_address, signup_date, status, list_id, password_hash)
                VALUES (?, ?, ?, CURDATE(), 'Pending', ?, ?)
                ON DUPLICATE KEY UPDATE
                    first_name=VALUES(first_name), last_name=VALUES(last_name),
                    list_id=VALUES(list_id), status='Pending', password_hash=VALUES(password_hash)
            ");
            $stmt->execute([$first_name, $last_name, $email, $list_id, $hash]);
            $sub_success = "Thank you! Your subscription is pending approval.";
        } catch (Exception $e) {
            $sub_error = "Email already exists or error occurred.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe to Newsletter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h1 class="text-center mb-5 text-primary fw-bold">Join Our Newsletter</h1>

                    <?php if ($sub_success): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                            <h3 class="text-success mt-4"><?= htmlspecialchars($sub_success) ?></h3>
                            <a href="index.php" class="btn btn-outline-primary mt-3">Subscribe Again</a>
                        </div>
                    <?php else: ?>
                        <?php if ($sub_error): ?>
                            <div class="alert alert-danger mb-4"><?= htmlspecialchars($sub_error) ?></div>
                        <?php endif; ?>

                        <form method="post" class="row g-4 mb-5">
                            <input type="hidden" name="public_subscribe" value="1">
                            <div class="col-md-6">
                                <input type="text" name="first_name" class="form-control form-control-lg" placeholder="First Name *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="last_name" class="form-control form-control-lg" placeholder="Last Name">
                            </div>
                            <div class="col-12">
                                <input type="email" name="email_address" class="form-control form-control-lg" placeholder="Your Email *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="password" name="password" class="form-control form-control-lg" placeholder="Create Password *" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <input type="password" name="confirm_password" class="form-control form-control-lg" placeholder="Confirm Password *" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Choose Newsletter:</label>
                                <select name="list_id" class="form-select form-select-lg" required>
                                    <option value="">-- Select a list --</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT list_id, list_name FROM Email_List ORDER BY list_name");
                                    while ($list = $stmt->fetch()) {
                                        echo "<option value='{$list['list_id']}'>" . htmlspecialchars($list['list_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-success btn-lg px-5">Subscribe Now</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <hr class="my-5">
                    <div class="text-center">
                        <p class="text-muted mb-3">Employee access only</p>
                        <?php if ($login_error): ?>
                            <div class="alert alert-danger d-inline-block mb-3"><?= htmlspecialchars($login_error) ?></div>
                        <?php endif; ?>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#empLogin">Employee Login</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Login Modal -->
<div class="modal fade" id="empLogin">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Employee Login</h5>
                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <input type="hidden" name="employee_login" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Employee Email</label>
                        <input type="email" name="email" class="form-control form-control-lg" placeholder="mona.suleiman@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-lg px-5">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>