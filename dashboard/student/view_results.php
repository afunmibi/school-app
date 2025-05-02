<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../student/login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch approved exam results
$query = "SELECT exam_score FROM exam_results WHERE student_id = (SELECT id FROM students WHERE student_id = ?) AND status = 'approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 bg-white shadow p-4 rounded">
        <h4 class="text-center text-success mb-4">Your Approved Results</h4>
        <a href="download_results.php" target="_blank" class="btn btn-primary w-100 mt-3">Download Results as PDF</a>


        <?php if ($results->num_rows > 0): ?>
            <ul class="list-group">
                <?php while ($row = $results->fetch_assoc()): ?>
                    <li class="list-group-item">Exam Score: <?= htmlspecialchars($row['exam_score']) ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-danger text-center">No approved results available.</p>
        <?php endif; ?>

        <hr>
        <a href="dashboard.php" class="btn btn-secondary w-100">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
