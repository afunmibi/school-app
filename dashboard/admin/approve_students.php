<?php
session_start();
include "../../config.php";

// Redirect if not an admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch pending pre-registrations
$query = "SELECT * FROM pre_registration WHERE status = 'pending'";
$result = $conn->query($query);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // Update the status to approved
        $stmt = $conn->prepare("UPDATE pre_registration SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Create student login
        $unique_id = $conn->prepare("SELECT unique_id FROM pre_registration WHERE id = ?");
        $unique_id->bind_param("i", $id);
        $unique_id->execute();
        $result = $unique_id->get_result();
        $student = $result->fetch_assoc();
        
        $password = password_hash('defaultPassword123', PASSWORD_DEFAULT); // default password, can be changed
        $stmt_login = $conn->prepare("INSERT INTO student_login (pre_reg_id, unique_id, password) VALUES (?, ?, ?)");
        $stmt_login->bind_param("iss", $id, $student['unique_id'], $password);
        $stmt_login->execute();
        
        header("Location: approve_students.php");
        exit;
    } elseif ($action == 'reject') {
        // Update the status to rejected
        $stmt = $conn->prepare("UPDATE pre_registration SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        header("Location: approve_students.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Approve Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h4 class="text-primary mb-4">Pending Pre-Registrations</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-success">Approve</a>
                        <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-danger">Reject</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
