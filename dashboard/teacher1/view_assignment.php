<?php
session_start();
include "../../config.php";

// Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned class
$class_assigned = '';
$stmt = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($class_assigned);
$stmt->fetch();
$stmt->close();
$class_assigned = trim($class_assigned ?? '');

// Fetch assignments posted by this teacher for their class
$assignments = [];
if (!empty($class_assigned)) {
    $stmt = $conn->prepare("
        SELECT id, title, subject, details, due_date, date_posted
        FROM assignments
        WHERE teacher_id = ? AND class_assigned = ?
        ORDER BY date_posted DESC
    ");
    $stmt->bind_param("is", $teacher_id, $class_assigned);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Assignments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4 text-primary">My Posted Assignments</h2>
    <?php if (!empty($class_assigned)): ?>
        <?php if (!empty($assignments)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Details</th>
                            <th>Due Date</th>
                            <th>Date Posted</th>
                            <th>Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($assignments as $row): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['details'])) ?></td>
                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                <td><?= htmlspecialchars($row['date_posted']) ?></td>
                                <td>
                                    <a href="view_submissions.php?assignment_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View Submissions</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No assignments posted yet for your class.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning">You are not currently assigned to a class. Please contact an administrator.</div>
    <?php endif; ?>
    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>