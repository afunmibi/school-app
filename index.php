<?php
session_start();
include "./config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($role == 'admin' || $role == 'teacher') {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
    } elseif ($role == 'student') {
        $stmt = $conn->prepare("SELECT * FROM student_login WHERE unique_id = ?");
        $stmt->bind_param("s", $username);
    } else {
        echo "<script>alert('Invalid role selected.');</script>";
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['role'] = $role;

            if ($role == 'admin' || $role == 'teacher') {
                $_SESSION['user_id'] = $user['id'];
            } else {
                $_SESSION['student_id'] = $user['unique_id']; // Corrected session key
            }

            // Redirect based on role
            switch ($role) {
                case 'admin':
                    header("Location: dashboard/admin/dashboard.php");
                    break;
                case 'teacher':
                    header("Location: teacher1\dashboard.php");
                    break;
                case 'student':
                    header("Location: dashboard/student/dashboard.php");
                    break;
            }
            exit;
        } else {
            echo "<script>alert('Invalid password.');</script>";
        }
    } else {
        echo "<script>alert('User not found.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="" method="POST" class="p-4 border shadow rounded bg-white">
                    <h4 class="text-center mb-4 text-primary">User Login</h4>

                    <div class="mb-3">
                        <label for="role">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Email / Unique ID</label>
                        <input type="text" name="username" class="form-control" required>
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
</body>
</html>
