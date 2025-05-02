<?php
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
// echo '<a href="download_results.php" class="btn btn-primary">Download Students Results (Excel)</a>';

// Fetch pre-registered students

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Welcome, <?php echo htmlspecialchars($admin_data['full_name'] ?? 'Admin'); ?></h4>

            <p><a href="../../download_results.php" class="btn btn-success mb-4">Download Students Results (Excel)</a></p>

            <h5>Manage Student Registrations</h5>
            <form method="POST" action="approve_registration.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Registration</button>
            </form>

            <hr>

            <h5>Approve Student Results</h5>
            <form method="POST" action="approve_results.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <button type="submit" class="btn btn-primary">Approve Results</button>
            </form>

            <hr>
            <p class="text-center"><a href="../../logout.php" class="btn btn-danger">Logout</a></p>
        </div>
    </div>
    <div class=" container mt-5 mb-5 text-center">
        <h4 class="text-primary mb-4 text-center">Pre-Registered Students</h4>
        <p>Click the button below to view all pre-registered students.</p>
        <a href="pending_registration.php" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">View All Pre-Registered Students</button></a>
        <br><br>
        <a href="approved_registration.php" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">View Approved Students</button></a>
        <br><br>
        <a href="rejected_registration.php" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">View Rejected Students</button></a>
        <br><br>
        <!-- <a href="" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">View Pre-Registered Students</button></a> -->
    </div>

    <!-- add teacher -->
     <div class="container mt-5 mb-5 text-center">
        <h4 class="text-primary mb-4 text-center">Manage Teacher</h4>
        <p>Click the button below to add a new teacher.</p>
        <a href="add_teacher.php" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Manage Teacher</button></a>
        <br><br>
        </div>
    
<!-- Manage Student -->
<div class="container mt-5 mb-5 text-center">
        <h4 class="text-primary mb-4 text-center">Manage Students</h4>
        <p>Click the button below to  Manage Students.</p>
        <a href="add_student.php" ><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Manage Students</button></a>
        <br><br>
        </div>
</body>
</html>