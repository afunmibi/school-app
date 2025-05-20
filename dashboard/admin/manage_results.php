<?php


session_start();
include "../../config.php";

// Only allow admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../../index.php");
    exit;
}

// Fetch all results with student name and class
$results = $conn->query("
    SELECT r.*, s.full_name, s.class_assigned
    FROM final_exam_results r
    LEFT JOIN students s ON r.student_id = s.id
    ORDER BY r.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h4 class="mb-4 text-primary">Manage Results</h4>
    <a href="add_result.php" class="btn btn-success mb-3">Add New Result</a>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Student Class</th>
                <th>Subject</th>
                <th>Assessment</th>
                <th>Exam Score</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sn = 1;
        if ($results && $results->num_rows > 0):
            while ($row = $results->fetch_assoc()):
        ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td><?= htmlspecialchars($row['student_id']) ?></td>
                <td><?= htmlspecialchars($row['full_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['class_assigned'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= htmlspecialchars($row['assessments'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['exam_scores'] ?? 0) ?></td>
                <td><?= ($row['assessments'] ?? 0) + ($row['exam_scores'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <a href="edit_result.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_result.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this result?')">Delete</a>
                </td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="10" class="text-center">No results found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>