<?php
session_start();
include "../../config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $unique_id = trim($_POST['unique_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($unique_id) || empty($password)) {
        $message = "Please enter both Unique ID and Password.";
    } else {
        // Fetch student login data using unique_id
        $stmt = $conn->prepare("SELECT * FROM student_login WHERE unique_id = ?");
        $stmt->bind_param("s", $unique_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();

            // Password verification (passwords should be hashed and verified)
            if (password_verify($password, $student['pass'])) {
                $_SESSION['student_id'] = $student['unique_id'];
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "Student not found or not yet approved by admin.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="" method="POST" class="p-4 border shadow rounded bg-white">
                    <h4 class="text-center mb-4 text-primary">Student Login</h4>
                    <?php if ($message) echo "<div class='alert alert-danger text-center'>$message</div>"; ?>
                    <div class="mb-3">
                        <label for="unique_id">Unique ID</label>
                        <input type="text" name="unique_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
    <div> 
        <p>NIK4620</p>
    <p>Y5SqGvMh</p>
    <p>RUK1770</p>
    <p>srXhWUbT</p>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>