<?php
require 'db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: index.php");
    exit;
}

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $email_id   = (int)$_POST['email_id'];
    $list_id    = (int)$_POST['list_id'];
    $new_status = $_POST['status'];
    $allowed    = ['Active', 'Pending', 'Unsubscribed'];

    if (in_array($new_status, $allowed)) {
        try {
            $stmt = $pdo->prepare("UPDATE Users_List SET status = ? WHERE email_id = ? AND list_id = ?");
            $stmt->execute([$new_status, $email_id, $list_id]);
            $success_msg = "Status updated to <strong>$new_status</strong>!";
        } catch (Exception $e) {
            $error_msg = "Failed to update status.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= htmlspecialchars($_SESSION['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand fs-4">Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</span>
        <a href="logout.php" class="btn btn-outline-light">Logout</a>
    </div>
</nav>
<div class="container">
    <h2 class="text-center mb-5 text-white">Email Subscribers Management System</h2>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success_msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error_msg ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add New Subscriber -->
    <div class="card mb-4 shadow">
        <div class="card-header bg-success text-white">
            <h5>Add New Subscriber</h5>
        </div>
        <div class="card-body bg-white">
            <form action="./add-subscriber.php" method="post" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name">
                </div>
                <div class="col-md-3">
                    <input type="email" name="email_address" class="form-control" placeholder="Email Address" required>
                </div>
                <div class="col-md-2">
                    <select name="list_id" class="form-select" required>
                        <option value="">Choose List</option>
                        <?php
                        $lists = $pdo->query("SELECT list_id, list_name FROM Email_List ORDER BY list_name")->fetchAll();
                        foreach($lists as $list){
                            echo "<option value='{$list['list_id']}'>" . htmlspecialchars($list['list_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="text-center mb-5">
        <a href="export-csv.php" class="btn btn-primary btn-lg me-4">Export All to CSV</a>
        <button class="btn btn-info btn-lg" data-bs-toggle="modal" data-bs-target="#importModal">Import from CSV</button>
    </div>

    <!-- Subscribers Table -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>All Subscribers</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Signup Date</th>
                            <th>Status</th>
                            <th>List</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT u.email_id, u.list_id, u.first_name, u.last_name, 
                                   u.email_address, u.signup_date, u.status, l.list_name
                            FROM Users_List u
                            LEFT JOIN Email_List l ON u.list_id = l.list_id
                            ORDER BY u.signup_date DESC
                        ");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $fullName = trim($row['first_name'] . ' ' . ($row['last_name'] ?? ''));
                            $badgeClass = $row['status']=='Active' ? 'success' : ($row['status']=='Pending' ? 'warning' : 'secondary');
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($fullName) ?></strong></td>
                                <td><?= htmlspecialchars($row['email_address']) ?></td>
                                <td><?= $row['signup_date'] ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="email_id" value="<?= $row['email_id'] ?>">
                                        <input type="hidden" name="list_id" value="<?= $row['list_id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm d-inline w-auto">
                                            <option value="Active" <?= $row['status']=='Active'?'selected':'' ?>>Active</option>
                                            <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                                            <option value="Unsubscribed" <?= $row['status']=='Unsubscribed'?'selected':'' ?>>Unsubscribed</option>
                                        </select>
                                    </form>
                                    <span class="badge bg-<?= $badgeClass ?> ms-2"><?= $row['status'] ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['list_name'] ?? 'No List') ?></td>
                                <td>
                                    <a href='delete-subscriber.php?id=<?= $row['email_id'] ?>&list=<?= $row['list_id'] ?>'
                                       class='btn btn-sm btn-danger'
                                       onclick='return confirm("Delete this subscriber permanently?")'>
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Import Subscribers from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>CSV Format:</strong> First Name, Last Name, Email, List ID (optional)</p>
                <input type="file" id="csvFile" accept=".csv" class="form-control">
                <div id="importResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="importCSV()">Upload & Import</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function importCSV() {
    const fileInput = document.getElementById('csvFile');
    if (!fileInput.files[0]) return alert("Please select a CSV file!");
    const formData = new FormData();
    formData.append('csv_file', fileInput.files[0]);
    fetch('import-csv.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if(data.success){
                alert("Success! Imported " + data.imported + " subscribers");
                location.reload();
            } else {
                alert("Error: " + (data.message || "Failed"));
            }
        })
        .catch(() => alert("Upload failed"));
}
</script>
</body>
</html>
