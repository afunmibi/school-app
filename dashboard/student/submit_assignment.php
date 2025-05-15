<?php
session_start();
include "../../config.php";

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../../../index.php");
    exit;
}

$assignment_id = $_POST['assignment_id'] ?? $_GET['assignment_id'] ?? null;
$student_id = $_SESSION['student_id'];
$submission_message = "";
$submission = null;
$assignment = null;

if ($assignment_id) {
    // Get assignment deadline
    $stmt = $conn->prepare("SELECT title, due_date FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignment = $result->fetch_assoc();
    $stmt->close();

    if (!$assignment) {
        echo "❌ Assignment not found.";
        exit;
    }

    // Check deadline
    $today = date('Y-m-d');
    if ($today > $assignment['due_date']) {
        echo "❌ Deadline has passed. You can no longer submit this assignment.";
        exit;
    }

    // Check if already submitted
    $stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $assignment_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
    $stmt->close();

    if ($submission) {
        $submission_message = "<div class='alert alert-success'>✅ Assignment already submitted.";
        if (!empty($submission['submission_file'])) {
            $submission_message .= " <a href='{$submission['submission_file']}' target='_blank'>View File</a>";
        }
        if (!empty($submission['submission_text'])) {
            $submission_message .= "<br><strong>Your Answer:</strong><br>" . nl2br(htmlspecialchars($submission['submission_text']));
        }
        $submission_message .= "</div>";
    }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assignment_id'])) {
    $submission_text = trim($_POST['submission_text'] ?? '');
    $target_file = '';

    // Handle file upload
    if (!empty($_FILES["submission_file"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = basename($_FILES["submission_file"]["name"]);
        $target_file = $target_dir . time() . "_" . $filename;

        if (!move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
            $target_file = '';
            $submission_message = "<div class='alert alert-danger'>❌ File upload failed.</div>";
        }
    }

    if ($submission) {
        // Update existing submission
        $stmt = $conn->prepare("UPDATE assignment_submissions SET submission_text = ?, submission_file = ?, submitted_at = NOW() WHERE assignment_id = ? AND student_id = ?");
        $stmt->bind_param("ssii", $submission_text, $target_file, $assignment_id, $student_id);
    } else {
        // Insert new submission
        $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, submission_file) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $assignment_id, $student_id, $submission_text, $target_file);
    }

    if ($stmt->execute()) {
        $submission_message = "<div class='alert alert-success'>✅ Assignment submitted successfully.";
        if ($target_file) {
            $submission_message .= " <a href='$target_file' target='_blank'>View File</a>";
        }
        if ($submission_text) {
            $submission_message .= "<br><strong>Your Answer:</strong><br>" . nl2br(htmlspecialchars($submission_text));
        }
        $submission_message .= "</div>";
    } else {
        $submission_message = "<div class='alert alert-danger'>❌ Error submitting assignment: " . $stmt->error . "</div>";
    }

    $stmt->close();
}
?>

<!-- HTML Form for Submission -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow bg-white rounded">
        <h4 class="mb-4 text-primary">Submit Assignment</h4>

        <?= $submission_message ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="assignment_id" value="<?= htmlspecialchars($assignment_id) ?>">

            <div class="mb-3">
                <label for="submission_text" class="form-label">Type your assignment below</label>
                <textarea name="submission_text" class="form-control" rows="8" placeholder="Type your answer here..."><?= htmlspecialchars($submission['submission_text'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="submission_file" class="form-label">Or upload a file (optional)</label>
                <input type="file" name="submission_file" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary w-100">Submit Assignment</button>
        </form>

        <div class="mt-3">
            <a href="view_assignments.php" class="btn btn-secondary">Back to My Assignments</a>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
