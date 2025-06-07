<?php
// Start the session to manage user login state.
session_start();

// Include the database configuration file.
require_once "../../config.php";

// Enable full error reporting for development.
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Enable MySQLi error reporting to throw exceptions on errors, aiding debugging.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize a variable to store feedback messages for the user.
$message = "";

// --- Check if the student is already logged in ---
if (isset($_SESSION['student_id'])) {
    // If the 'student_id' session variable is set, redirect the student to the dashboard.
    header("Location: dashboard.php");
    exit; // Terminate script execution after redirection.
}

// --- Handle Login Form Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve the Unique ID and Password from the submitted form.
    $unique_id = trim($_POST['unique_id'] ?? '');
    $password = $_POST['password'] ?? '';

    // --- Input Validation ---
    if (empty($unique_id) || empty($password)) {
        // If either Unique ID or Password is empty, display an error message.
        $message = "<div class='alert alert-danger text-center'>Please enter both Unique ID and Temporary Password.</div>";
    } else {
        // --- Database Authentication ---
        // Prepare a secure database query to fetch the student's record from the 'student_login' table based on the Unique ID.
        $stmt = $conn->prepare("SELECT id, unique_id, student_numeric_id, full_name, password FROM student_login WHERE unique_id = ?");

        // Check if the prepare statement was successful.
        if (!$stmt) {
            // Log the database error if the prepare statement fails.
            error_log("Login query failed: " . $conn->error, 3, "/var/log/school_app_errors.log");
            $message = "<div class='alert alert-danger text-center'>System error occurred. Please try again later.</div>";
        } else {
            // Bind the Unique ID parameter to the prepared statement.
            $stmt->bind_param("s", $unique_id);

            // Execute the query.
            $stmt->execute();

            // Get the result set.
            $result = $stmt->get_result();

            // Fetch the student's data as an associative array.
            $student = $result->fetch_assoc();

            // Close the database statement.
            $stmt->close();

            // --- Password Verification and Session Management ---
            if ($student && password_verify($password, $student['password'])) {
                // If a student record is found and the password verification is successful:
                // Regenerate the session ID to enhance security against session fixation attacks.
                session_regenerate_id(true);

                // Store the student's information in session variables.
                $_SESSION['student_db_id'] = $student['id'];
                $_SESSION['student_unique_id'] = $student['unique_id'];
                $_SESSION['student_id'] = $student['student_numeric_id'];
                $_SESSION['student_full_name'] = $student['full_name'];

                // Redirect the student to their dashboard page.
                header("Location: dashboard.php");
                // header("Location: test.php");
                exit; // Terminate script execution after redirection.
            } else {
                // If no student record is found or the password verification fails, display an error message.
                $message = "<div class='alert alert-danger text-center'>Invalid Unique ID or Temporary Password.</div>";
            }
        }
    }
}

// --- Close the database connection securely ---
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1.1rem;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            transition: all 0.2s ease-in-out;
        }
        .alert {
            border-radius: 8px;
        }
        h4 {
            font-weight: 600;
            color: #007bff;
        }
        label {
            font-weight: 500;
        }
        .text-center p {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h4 class="text-center mb-4"><i class="bi bi-person-circle me-2"></i>Student Login</h4>
        <?= $message ?>
        <form action="" method="POST" class="row g-3">
            <div class="col-12">
                <label for="unique_id" class="form-label">Unique ID</label>
                <input type="text" name="unique_id" id="unique_id" class="form-control" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['unique_id'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label for="password" class="form-label">Temporary Password</label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
            </div>
        </form>
        <div class="text-center mt-4">
            <p>Don't have an account? <a href="registration/pre_register.php" class="text-primary">Pre-Register here</a></p>
        </div>
        <div class="text-center mt-3">
        <p>OLU9243, w4BnQxkH</p>
        <p>NIK4620, Y5SqGvMh</p>
        <p>RUK1770, Y5SqGvMh</p>
        <p class="text-muted">Need help? Contact your class teacher or the school administration.</p>
        <p class="text-muted">© <?= date("Y") ?> Your School Name. All rights reserved.</p>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>