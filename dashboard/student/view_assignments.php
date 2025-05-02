<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../student/login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student class
$class_query = "SELECT class FROM students WHERE student_id = ?";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$class = $student['class'];

// Fetch assignments for this class
$query = "SELECT title, description, date_given FROM assignments WHERE class = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $class);
$stmt->execute();
$assignments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Assignments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 bg-white shadow p-4 rounded">
        <h4 class="text-center text-info mb-4">Assignments for Your Class</h4>

        <?php if ($assignments->num_rows > 0): ?>
            <?php while ($row = $assignments->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($row['description'])) ?></p>
                        <small class="text-muted">Given on: <?= htmlspecialchars($row['date_given']) ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-danger text-center">No assignments available.</p>
        <?php endif; ?>

        <hr>
        <a href="dashboard.php" class="btn btn-secondary w-100">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
