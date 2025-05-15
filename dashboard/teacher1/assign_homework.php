<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $homework_details = trim($_POST['homework_details']);
    $teacher_id = $_SESSION['user_id'];

    // Validate student ID
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Assign homework
        $stmt_insert = $conn->prepare("INSERT INTO homework (student_id, homework_text, teacher_id, assigned_date) VALUES (?, ?, ?, NOW())");
        $stmt_insert->bind_param("ssi", $student_id, $homework_details, $teacher_id);
        if ($stmt_insert->execute()) {
            $message = "<div class='alert alert-success'>Homework assigned successfully. <a href='dashboard.php'>Go Back</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error assigning homework.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Invalid Student ID. <a href='dashboard.php'>Go Back</a></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Homework</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Assign Homework</h4>
        </div>
        <div class="card-body">
            <?= $message ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="homework_details" class="form-label">Homework Details</label>
                    <textarea name="homework_details" id="homework_details" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Assign Homework</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>