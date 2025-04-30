<?php
include "../config.php";

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM pre_registration WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "Email already registered.";
    } else {
        // Generate unique ID
        $unique_id = strtoupper(substr($name, 0, 3)) . rand(1000, 9999);

        $stmt = $conn->prepare("INSERT INTO pre_registration (full_name, phone, email, unique_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $email, $unique_id);

        if ($stmt->execute()) {
            $message = "Registration successful. Your Unique ID is: <strong>$unique_id</strong>. Please wait for admin approval.";
        } else {
            $message = "Error during registration.";
        }
    }
}
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
        <form method="POST" action="">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Submit Pre-Registration</button>
        </form>
    </div>
</div>
</body>
</html>
