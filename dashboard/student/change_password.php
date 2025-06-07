<?php
// Enable strict error reporting for development.
declare(strict_types=1);

// Start the session.
session_start();

// Include the database configuration file.
require_once "../../config.php";

// Check if the student is logged in. Redirect to login page if not.
if (!isset($_SESSION['student_unique_id']) || empty($_SESSION['student_unique_id'])) {
    header("Location: student_login.php");
    exit;
}

$studentUniqueId = $_SESSION['student_unique_id'];
$message = "";

// Handle form submission for changing password.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $message = "<div class='alert alert-danger text-center'>Please enter both new password and confirm password.</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger text-center'>New password and confirm password do not match.</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-warning text-center'>New password must be at least 6 characters long.</div>";
    } else {
        // Hash the new password.
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        try {
            // Prepare an SQL query to update the password.
            $stmt = $conn->prepare("UPDATE student_login SET password = ? WHERE unique_id = ?");

            if ($stmt) {
                $stmt->bind_param("ss", $hashed_password, $studentUniqueId);

                if ($stmt->execute()) {
                    // Successfully updated password, now update temporary_password
                    $updateTempPasswordStmt = $conn->prepare("UPDATE student_login SET temporary_password = NULL WHERE unique_id = ?");
                    if ($updateTempPasswordStmt) {
                        $updateTempPasswordStmt->bind_param("s", $studentUniqueId);
                        $updateTempPasswordStmt->execute();
                        $updateTempPasswordStmt->close();
                        $message = "<div class='alert alert-success text-center'>Password changed successfully!</div>";
                    } else {
                        error_log("Change Password Error: Prepare query for temporary_password failed: " . $conn->error);
                        $message = "<div class='alert alert-warning text-center'>Password changed successfully, but failed to update temporary password.</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger text-center'>Error updating password. Please try again later.</div>";
                }

                $stmt->close();
            } else {
                error_log("Change Password Error: Prepare query for password failed: " . $conn->error);
                $message = "<div class='alert alert-danger text-center'>System error occurred. Please try again later.</div>";
            }
        } catch (Exception $e) {
            error_log("Change Password Error: Database error: " . $e->getMessage());
            $message = "<div class='alert alert-danger text-center'>Database error occurred. Please try again later.</div>";
        }
    }
}

// Close the database connection.
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .container { max-width: 600px; margin: 50px auto; background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #007bff; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; }
        .btn-primary { background-color: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .btn-primary:hover { background-color: #0056b3; }
        .alert { margin-top: 20px; }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 15px; }
        .btn-secondary { background-color: #6c757d; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .btn-secondary:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Change Your Password</h2>
        <?= $message ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>