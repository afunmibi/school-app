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

// Approve the pre-registration record when 'id' is passed via GET
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo $id = (int)$_GET['id'];

    $conn->query("UPDATE pre_registration1 SET status = 'approved' WHERE id = $id");
}

// Insert approved pre-registrations into students table if not already present

$insert_sql = "
    INSERT INTO students (full_name, phone_no, email_address, unique_id, status)
    SELECT pr.full_name, pr.phone_no, pr.email_address, pr.unique_id, 'approved'
    FROM pre_registration1 pr
    LEFT JOIN students s ON pr.unique_id = s.unique_id
    WHERE pr.status = 'approved' AND s.unique_id IS NULL
";
if (!$conn->query($insert_sql)) {
    echo "<div class='alert alert-danger'>Insert Error: " . htmlspecialchars($conn->error) . "</div>";
}

// Update existing students with the latest info from pre_registration1
$update_sql = "
    UPDATE students s
    JOIN pre_registration1 pr ON s.unique_id = pr.unique_id
    SET 
        s.full_name = pr.full_name,
        s.phone_no = pr.phone_no,
        s.status = 'approved'
    WHERE pr.status = 'approved'
";
if (!$conn->query($update_sql)) {
    echo "<div class='alert alert-danger'>Update Error: " . htmlspecialchars($conn->error) . "</div>";
}

// Fetch all approved students from pre_registration1
$query = "SELECT id, full_name, phone_no, email_address, unique_id, status, created_at 
          FROM pre_registration1 WHERE status = 'approved'";
$result = $conn->query($query);
if ($conn->error) {
    echo "<div class='alert alert-danger'>Select Error: " . htmlspecialchars($conn->error) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Approved Students</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 mx-auto">
        <div class="col-md-10 offset-md-1 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">All Approved Students</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Unique ID</th>
                        <th>Status</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): 
                        $sn = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['phone_no']) ?></td>
                                <td><?= htmlspecialchars($row['email_address']) ?></td>
                                <td><?= htmlspecialchars($row['unique_id']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No approved students found.</td>
                            </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-danger">Go Back</a>
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>