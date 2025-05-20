<?php
session_start();
include "../../config.php";

// Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$teacher_class_assigned = '';
$form_disabled = false;
$page_message = ""; // For messages like 'not assigned to class'
$message = "";      // For form submission feedback

// Fetch teacher's assigned class from the 'teachers' table
$stmt_class = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
if ($stmt_class) {
    $stmt_class->bind_param("i", $teacher_id);
    $stmt_class->execute();
    $result_class = $stmt_class->get_result();
    if ($row_class = $result_class->fetch_assoc()) {
        $teacher_class_assigned = trim($row_class['class_assigned']);
    }
    $stmt_class->close();
} else {
    error_log("Error preparing statement to fetch teacher's class: " . $conn->error);
    $page_message = "<div class='alert alert-danger'>Could not retrieve teacher details. Please try again later.</div>";
    $form_disabled = true;
}

if (empty($teacher_class_assigned) && !$form_disabled) {
    $page_message = "<div class='alert alert-warning'>You are not currently assigned to a class. Assignments can only be posted for an assigned class. Please contact an administrator.</div>";
    $form_disabled = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Homework</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .assignment-form-card {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px rgba(30,41,59,0.08);
            padding: 2rem 2rem 1.5rem 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="assignment-form-card">
            <h2 class="text-center text-primary mb-3">Post Assignment</h2>
            <?= $page_message ?>
            <?= $message ?>

            <?php if (!$form_disabled): ?>
                <div class="alert alert-info text-center mb-4" role="alert">
                    You are posting an assignment for: <strong>Class <?= htmlspecialchars($teacher_class_assigned) ?></strong>.
                </div>
                <form method="POST" action="post_assignment.php">
                    <input type="hidden" name="class" value="<?= htmlspecialchars($teacher_class_assigned) ?>">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assignment Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Assignment Title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assignment Details</label>
                        <textarea name="details" class="form-control" placeholder="Assignment Details" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-info w-100">Post Assignment</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="text-center mb-3">
            <a href="./dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>