
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

// Handle reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    $reject_id = intval($_POST['reject_id']);
    $stmt = $conn->prepare("UPDATE pre_registration1 SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $reject_id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Registration rejected successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error rejecting registration.</div>";
    }
    $stmt->close();
}

// Fetch all pre-registrations
$query = "SELECT id, full_name, email_address, phone_no, status FROM pre_registration1";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rejected/Reject Student Registrations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="col-md-10 offset-md-1 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Registrations</h4>
        <?php if ($message) echo $message; ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
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
                            <td>
                                <?php
                                if ($row['status'] === 'rejected') {
                                    echo '<span class="badge bg-danger">Rejected</span>';
                                } elseif ($row['status'] === 'approved') {
                                    echo '<span class="badge bg-success">Approved</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                                }
                                ?>
                            </td>
                            
                            <td>
                                <?php if ($row['status'] !== 'rejected'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="reject_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this registration?')">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No registrations found.</td>
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