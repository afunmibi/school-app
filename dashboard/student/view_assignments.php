<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Check if logged in as student
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch assignments for this student
$stmt = $conn->prepare("SELECT a.id, a.subject, a.title, a.description, a.due_date, t.full_name AS teacher_name 
                        FROM assignments a 
                        JOIN teachers t ON a.teacher_id = t.id 
                        WHERE a.class = (SELECT class FROM students WHERE student_id = ?) 
                        ORDER BY a.due_date ASC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Assignments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="mb-4 text-primary">📘 My Assignments</h3>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Teacher</th>
                    <th>Submit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                        <td><?= date("F j, Y", strtotime($row['due_date'])) ?></td>
                        <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                        <td>
                            <form action="submit_assignment.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?= $row['id'] ?>">
                                <input type="file" name="submission_file" required>
                                <button type="submit" class="btn btn-sm btn-success mt-1">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-danger">No assignments found.</p>
    <?php endif; ?>
</div>
</body>
</html>