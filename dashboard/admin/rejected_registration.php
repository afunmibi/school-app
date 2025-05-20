<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch only rejected pre-registrations
$query = "SELECT id, full_name, email_address, phone_no, status, rejection_reason FROM pre_registration1 WHERE status = 'rejected'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rejected Student Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="col-md-10 offset-md-1 p-4 shadow rounded bg-white">
        <h4 class="text-danger mb-4 text-center">Rejected Student Registrations</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): 
                    $sn = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['email_address']) ?></td>
                            <td><?= htmlspecialchars($row['phone_no']) ?></td>
                            <td><?= htmlspecialchars($row['rejection_reason']) ?></td>
                            <td>
                                <span class="badge bg-danger">Rejected</span>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No rejected registrations found.</td>
                        </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">Go Back</a>
        </p>
    </div>
</div>
</body>
</html>