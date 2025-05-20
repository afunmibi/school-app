<?php

include "../../../config.php";

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if required POST variables exist
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone_no'] ?? '');
    $email = trim($_POST['email_address'] ?? '');

    // Validate inputs
    if (empty($full_name) || empty($phone) || empty($email)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } else {
        // Check if email already exists in pre_registration1 or student_login
        $check = $conn->prepare("
            SELECT email_address FROM pre_registration1 WHERE email_address = ?
            UNION
            SELECT email_address FROM student_login WHERE email_address = ?
        ");
        $check->bind_param("ss", $email, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already registered.";
        } else {
            // Generate unique IDs
            $unique_id = strtoupper(substr(preg_replace('/\s+/', '', $full_name), 0, 3)) . rand(1000, 9999);
            $registration_id = 'REG' . rand(10000, 99999);
            $plain_password = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"), 0, 8);

            // Insert into pre_registration1
            $status = 'Pending';
            $stmt = $conn->prepare("INSERT INTO pre_registration1 (registration_id, full_name, phone_no, email_address, unique_id, temporary_password, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $registration_id, $full_name, $phone, $email, $unique_id, $plain_password, $status);

            if ($stmt->execute()) {
                // Insert into student_login
                $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
                $login_stmt = $conn->prepare("INSERT INTO student_login (unique_id, email_address, full_name, pass) VALUES (?, ?, ?, ?)");
                $login_stmt->bind_param("ssss", $unique_id, $email, $full_name, $hashed_password);
                $login_stmt->execute();
                $login_stmt->close();

                // Insert or update students table with status from pre_registration1
                $student_stmt = $conn->prepare(
                    "INSERT INTO students (registration_id, full_name, phone_no, email_address, unique_id, status)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                        full_name = VALUES(full_name),
                        phone_no = VALUES(phone_no),
                        email_address = VALUES(email_address),
                        status = VALUES(status)"
                );
                $student_stmt->bind_param("ssssss", $registration_id, $full_name, $phone, $email, $unique_id, $status);
                $student_stmt->execute();
                $student_stmt->close();

                $message = "Registration successful. Your details are as follows:<br><br>
                    <strong>Registration ID:</strong> $registration_id<br>
                    <strong>Unique ID:</strong> $unique_id<br>
                    <strong>Temporary Password:</strong> $plain_password<br>
                    <strong>Email:</strong> $email<br><br>
                    Please keep your <strong>Registration ID</strong>, <strong>Unique ID</strong>, <strong>Email</strong>, and <strong>Password</strong> safe. You will be able to log in once your registration is approved by the admin.";
            } else {
                $message = "Error saving pre-registration: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Pre-Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 offset-md-3 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Pre-Registration</h4>
        <?php if ($message) echo "<div class='alert alert-info'>$message</div>"; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_no" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email_address" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Submit Pre-Registration</button>
        </form>
    </div>
</div>
</body>
</html>