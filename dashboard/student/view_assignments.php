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

// Fetch student's class
$class_stmt = $conn->prepare("SELECT class_assigned FROM students WHERE student_id = ?");
$class_stmt->bind_param("s", $student_id);
$class_stmt->execute();
$class_stmt->bind_result($student_class);
$class_stmt->fetch();
$class_stmt->close();

// Fetch assignments for this student
$stmt = $conn->prepare("SELECT a.id, a.subject, a.title, a.description, a.due_date, t.full_name AS teacher_name 
                        FROM assignments a 
                        JOIN teachers t ON a.teacher_id = t.id 
                        WHERE a.class = ? 
                        ORDER BY a.due_date ASC");
$stmt->bind_param("s", $student_class);
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
                    <th>Status / Submit</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    // Check if this assignment has been submitted
                    $sub_stmt = $conn->prepare("SELECT submission_file FROM assignments WHERE id = ? AND student_id = ?");
                    $sub_stmt->bind_param("ii", $row['id'], $student_id);
                    $sub_stmt->execute();
                    $sub_stmt->bind_result($submission_file);
                    $sub_stmt->fetch();
                    $sub_stmt->close();
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                        <td><?= date("F j, Y", strtotime($row['due_date'])) ?></td>
                        <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                        <td>
                            <?php if (!empty($submission_file)): ?>
                                <span class="badge bg-success mb-1">Submitted</span>
                                <a href="<?= htmlspecialchars($submission_file) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                            <?php else: ?>
                                <form action="submit_assignment.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="assignment_id" value="<?= $row['id'] ?>">
                                    <input type="file" name="submission_file" required>
                                    <button type="submit" class="btn btn-sm btn-success mt-1">Submit</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-danger">No assignments found.</p>
    <?php endif; ?>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <a href="../logout.php" class="btn btn-danger">Logout</a>
</div>
</body>
</html>