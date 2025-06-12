<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_user_id = $_SESSION['user_id']; // This is users.id
$assignment_id = $_GET['assignment_id'] ?? null;
$assignment_details = null;
$submissions = [];
$message = "";

if (!$assignment_id) {
    $message = "<div class='alert alert-danger'>❌ No assignment ID specified.</div>";
} else {
    // Fetch assignment details and verify it belongs to this teacher
    $stmt_assignment = $conn->prepare("SELECT id, title, description, subject, class_assigned, due_date, date_posted FROM assignments WHERE id = ? AND teacher_id = ?");
    if ($stmt_assignment) {
        $stmt_assignment->bind_param("ii", $assignment_id, $teacher_user_id);
        $stmt_assignment->execute();
        $result_assignment = $stmt_assignment->get_result();
        $assignment_details = $result_assignment->fetch_assoc();
        $stmt_assignment->close();

        if (!$assignment_details) {
            $message = "<div class='alert alert-danger'>❌ Assignment not found or does not belong to you.</div>";
        } else {
            // Fetch all submissions for this assignment
            // Join with students table to get student name and official student_id
            $stmt_submissions = $conn->prepare("
                SELECT 
                    s.full_name, 
                    s.student_id AS official_student_id, 
                    sub.submission_text, 
                    sub.submission_file, 
                    sub.submitted_at
                FROM assignments_submissions sub
                JOIN students s ON sub.student_id = s.id /* student_id in submissions is FK to students.id */
                WHERE sub.assignment_id = ?
                ORDER BY s.full_name ASC
            ");
            if ($stmt_submissions) {
                $stmt_submissions->bind_param("i", $assignment_id);
                $stmt_submissions->execute();
                $result_submissions = $stmt_submissions->get_result();
                while ($row = $result_submissions->fetch_assoc()) {
                    $submissions[] = $row;
                }
                $stmt_submissions->close();

                if (empty($submissions) && empty($message)) {
                    $message = "<div class='alert alert-info'>No submissions received for this assignment yet.</div>";
                }

            } else {
                 error_log("Failed to prepare statement to fetch submissions: " . $conn->error);
                 if(empty($message)) $message = "<div class='alert alert-danger'>Error fetching submissions. Please contact support.</div>";
            }
        }
    } else {
        error_log("Failed to prepare statement to fetch assignment details: " . $conn->error);
        if(empty($message)) $message = "<div class='alert alert-danger'>Error fetching assignment details. Please contact support.</div>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <title>Submissions for Assignment</title>
    <style>
        .submission-text-preview {
            max-height: 100px;
            overflow-y: auto;
            white-space: pre-wrap; /* Preserve line breaks */
            word-wrap: break-word; /* Break long words */
            font-size: 0.9em;
            color: #555;
            border: 1px solid #eee;
            padding: 5px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="bg-white p-4 rounded shadow">
        <?php if ($assignment_details): ?>
            <h4 class="mb-3">Submissions for: <span class="text-primary"><?= htmlspecialchars($assignment_details['title']) ?></span></h4>
            <p class="text-muted">Subject: <?= htmlspecialchars($assignment_details['subject']) ?> | Class: <?= htmlspecialchars($assignment_details['class_assigned']) ?> | Due: <?= htmlspecialchars($assignment_details['due_date']) ?></p>
            <hr>
        <?php endif; ?>

        <?= $message ?>

        <?php if (!empty($submissions)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Submitted At</th>
                            <th>Submission File</th>
                            <th>Submission Text</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><?= htmlspecialchars($submission['full_name']) ?></td>
                                <td><?= htmlspecialchars($submission['official_student_id']) ?></td>
                                <td><?= htmlspecialchars($submission['submitted_at']) ?></td>
                                <td>
                                    <?php if (!empty($submission['submission_file'])): ?>
                                        <?php 
                                            // Assuming submission_file is relative to school-app root (e.g., uploads/assignments/...)
                                            $file_path = '../../' . htmlspecialchars($submission['submission_file']); 
                                        ?>
                                        <a href="<?= $file_path ?>" target="_blank" class="btn btn-sm btn-success">View File</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No File</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($submission['submission_text'])): ?>
                                        <div class="submission-text-preview"><?= nl2br(htmlspecialchars($submission['submission_text'])) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Text</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-3 text-center">
            <a href="dashboard.php" class="btn btn-secondary me-2">Back to Dashboard</a>
            <?php if ($assignment_details): ?>
                 <a href="view_assignment.php" class="btn btn-info">View All Posted Assignments</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>