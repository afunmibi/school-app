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
    <a href="../teacher1/manage_record_ca_exam_results.php" class="btn btn-success mb-3">Add New Result</a>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th> <!-- Corresponds to final_exam_results.id (primary key, via $sn++) -->
                <th>Student ID</th> <!-- final_exam_results.student_id -->
                <th>Student Name</th> <!-- students.full_name (from JOIN) -->
                <th>Class</th> <!-- final_exam_results.class -->
                <th>Subject</th> <!-- final_exam_results.subject -->
                <th>Term</th> <!-- final_exam_results.term -->
                <th>Session</th> <!-- final_exam_results.session -->
                <th>Assessments</th> <!-- final_exam_results.assessments -->
                <th>Exam Score</th> <!-- final_exam_results.exam_score -->
                <th>Final Score</th> <!-- final_exam_results.final_score -->
                <th>Status</th> <!-- final_exam_results.status -->
                <th>Result Date</th> <!-- final_exam_results.result_date -->
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
                <td><?= htmlspecialchars($row['full_name'] ?? '-') ?></td> <!-- s.full_name -->
                <td><?= htmlspecialchars($row['class'] ?? '-') ?></td> <!-- r.class -->
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= htmlspecialchars($row['term'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['session'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['assessments'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['exam_score'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['final_score'] ?? 0) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['result_date'] ?? '-') ?></td>
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