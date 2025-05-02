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

// Fetch all rejected students from pre_registration1
$query = "SELECT * FROM pre_registration1 WHERE status = 'rejected';";
$result = $conn->query($query);
if ($conn->error) {
    echo "<div class='alert alert-danger'>Select Error: " . htmlspecialchars($conn->error) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Rejected Students</title>
</head>
<body>
    <div class="container mt-5 mx-auto">
        <div class="col-md-10 offset-md-1 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Rejected Student Registrations</h4>
            <?php if ($result && $result->num_rows > 0): ?>
                <table class="table table-bordered table-striped">
                <thead class="table-dark">
    <tr>
        <th>#</th>
        <th>Full Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Status</th>
        <th>Rejected On</th>
    </tr>
</thead>
                    <tbody>
    <?php 
    $sn = 1;
    while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['phone_no'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['email_address'] ?? '') ?></td>
             <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['registered_at']) ?></td>
        </tr>
    <?php endwhile; ?>
</tbody>
                </table>
            <?php else: ?>
                <p class="text-danger text-center">No rejected students found.</p>
            <?php endif; 
            $conn->close();
            ?>
            <div class="text-center mt-4">
                <a href="../admin/dashboard.php" class="btn btn-danger">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html>