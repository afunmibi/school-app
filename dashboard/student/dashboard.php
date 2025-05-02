<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../index.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student info from the main students table
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 bg-white shadow p-4 rounded">
        <h4 class="text-center text-primary">Welcome, <?= htmlspecialchars($student['full_name'] ?? 'Student') ?></h4>

        <hr>
        <p><strong>Class:</strong> <?= htmlspecialchars($student['class'] ?? '-') ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($student['status'] ?? '-') ?></p>

        <hr>
        <h5>Quick Access</h5>
        <a href="view_results.php" class="btn btn-success w-100 mb-2">View Results</a>
        <a href="view_assignments.php" class="btn btn-info w-100 mb-2">View Assignments</a>

        <hr>
        <a href="../../logout.php" class="btn btn-danger w-100">Logout</a>
    </div>
</div>
</body>
</html>
