<?php
// filepath: c:\xampp\htdocs\PHP-Projects-Here\school-app\dashboard\admin\add_student.php
session_start();
include "../../config.php";

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['full_name'] ?? '');
    $email_address  = trim($_POST['email_address'] ?? '');
    $class_assigned = trim($_POST['class_assigned'] ?? '');
    $student_id     = trim($_POST['student_id'] ?? '');

    // You can add more fields as needed

    // Check if student exists by student_id
    $stmt_check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt_check->bind_param("s", $student_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Update existing student
        $stmt = $conn->prepare("UPDATE students SET full_name=?, email_address=?, class_assigned=? WHERE student_id=?");
        $stmt->bind_param("ssss", $full_name, $email_address, $class_assigned, $student_id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Student updated and assigned to class successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error updating student.</div>";
        }
        $stmt->close();
    } else {
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO students (full_name, email_address, class_assigned, student_id, password) VALUES (?, ?, ?, ?, ?)");
        $default_password = password_hash('changeme', PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $full_name, $email_address, $class_assigned, $student_id, $default_password);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Student added and assigned to class successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding student.</div>";
        }
        $stmt->close();
    }
    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Student to Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h3 class="mb-4">Assign Student to Class</h3>
    <?php if (!empty($message)) echo $message; ?>
    <form method="POST" action="">
        <div class="mb-2">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" required class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label">Email Address</label>
            <input type="email" name="email_address" required class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label">Student ID</label>
            <input type="text" name="student_id" required class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label">Class</label>
            <select name="class_assigned" required class="form-select">
                <option value="">--Select--</option>
                <option value="Basic 1">Basic 1</option>
                <option value="Basic 2">Basic 2</option>
                <option value="Basic 3">Basic 3</option>
                <option value="Basic 4">Basic 4</option>
                <option value="Basic 5">Basic 5</option>
                <option value="Basic 6">Basic 6</option>
                <option value="JSS1">JSS1</option>
                <option value="JSS2">JSS2</option>
                <option value="JSS3">JSS3</option>
                <option value="SS1">SS1</option>
                <option value="SS2">SS2</option>
                <option value="SS3">SS3</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Assign Class</button>
    </form>

    <hr>
    <h4 class="mt-4">All Students</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Class</th>
                <th>Email</th>
                <th>Student ID</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sn = 1;
            $res = $conn->query("SELECT * FROM students ORDER BY id DESC");
            while ($row = $res->fetch_assoc()):
            ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                <td><?= htmlspecialchars($row['email_address']) ?></td>
                <td><?= htmlspecialchars($row['student_id']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>