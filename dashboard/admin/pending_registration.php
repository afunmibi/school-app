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

$message = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Pending Student Registrations</title>
</head>
<body>
    <div class="container mt-5 mx-auto">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Pending Student Registrations</h4>
            <?php if ($message) echo $message; ?>

            <?php
            // Fetch pre-registered students with pending status
            $query = "SELECT id, full_name, email_address, phone_no FROM pre_registration1 WHERE status = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                echo "<div class='alert alert-danger'>Error preparing fetch query: " . htmlspecialchars($conn->error) . "</div>";
            } else {
                $status = 'pending';
                $stmt->bind_param("s", $status);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0): ?>
                    <h5>Pending Pre-Registrations</h5>
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email_address']) ?></td>
                                    <td><?= htmlspecialchars($row['phone_no']) ?></td>
                                    <td>
                                        <a href="approved_registration.php?id=<?= urlencode($row['id']) ?>" class="btn btn-success btn-sm">Approve</a>
                                        <a href="rejected_registration.php?id=<?= urlencode($row['id']) ?>" class="btn btn-danger btn-sm">Reject</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-danger text-center">No pending registrations found.</p>
                <?php endif;
                $stmt->close();
            }
            $conn->close();
            ?>

            <p class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-danger">Go Back</a>
            </p>
        </div>
    </div>
</body>
</html>