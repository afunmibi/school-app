<?php
session_start();
require "../../config.php";

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}
$student_unique_id = $_SESSION['student_unique_id'] ?? null;
$assignment_id = $_POST['assignment_id'] ?? $_GET['assignment_id'] ?? null;

$submission_message = "";
$submission = null;
$assignment_details = null;
$student_internal_id = null;

// Fetch the student's internal ID (students.id) using their unique_id
$stmt_student_pk = $conn->prepare("SELECT id FROM students WHERE unique_id = ?");
$stmt_student_pk->bind_param("s", $student_unique_id);
$stmt_student_pk->execute();
$result_student_pk = $stmt_student_pk->get_result();
$student_pk_data = $result_student_pk->fetch_assoc();
$stmt_student_pk->close();
$student_internal_id = $student_pk_data['id'] ?? null;

if ($assignment_id) {
    // Get assignment details
    $stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignment_details = $result->fetch_assoc();
    $stmt->close();

    if (!$assignment_details) {
        $submission_message = "<div class='alert alert-danger'>❌ Assignment not found.</div>";
        $assignment_id = null;
    } elseif (!$student_internal_id) {
        $submission_message = "<div class='alert alert-danger'>❌ Student profile error. Cannot submit.</div>";
        $assignment_id = null;
    } else {
        // Check deadline
        $today = date('Y-m-d');
        if ($today > $assignment_details['due_date']) {
            $submission_message = "<div class='alert alert-warning'>⚠️ Deadline has passed (" . htmlspecialchars($assignment_details['due_date']) . "). You can no longer submit this assignment.</div>";
            $assignment_id = null;
        } else {
            // Check if already submitted
            $stmt_check = $conn->prepare("SELECT submission_text, submission_file, submitted_at FROM assignments_submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1");
            $stmt_check->bind_param("ii", $assignment_id, $student_internal_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $submission = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($submission) {
                $submission_message = "<div class='alert alert-info'>✅ Assignment already submitted on " . htmlspecialchars(date("Y-m-d H:i", strtotime($submission['submitted_at']))) . ".";
                if (!empty($submission['submission_file'])) {
                    $submission_message .= " <a href='../../" . htmlspecialchars($submission['submission_file']) . "' target='_blank'>View Your File</a>";
                }
                if (!empty($submission['submission_text'])) {
                    $submission_message .= "<br><strong>Your Text:</strong><br>" . nl2br(htmlspecialchars($submission['submission_text']));
                }
                $submission_message .= " You can replace your submission if the deadline has not passed.</div>";
            }
        }
    }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assignment_id']) && $assignment_id && $student_internal_id && $assignment_details) {
    $assignment_id_post = $_POST['assignment_id'];
    $submission_text = trim($_POST['submission_text'] ?? '');
    $target_file_db_path = $submission['submission_file'] ?? '';
    $upload_error = false;

    // Handle file upload
    if (!empty($_FILES["submission_file"]["name"])) {
        $upload_dir_relative_to_root = "uploads/assignments/";
        $upload_dir_absolute = dirname(dirname(dirname(__FILE__))) . '/' . $upload_dir_relative_to_root;

        if (!is_dir($upload_dir_absolute)) {
            mkdir($upload_dir_absolute, 0777, true);
        }

        $filename = $student_internal_id . "_" . $assignment_id_post . "_" . time() . "_" . basename($_FILES["submission_file"]["name"]);
        $target_file_absolute_path = $upload_dir_absolute . $filename;

        if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file_absolute_path)) {
            $target_file_db_path = $upload_dir_relative_to_root . $filename;
        } else {
            $submission_message = "<div class='alert alert-danger'>❌ File upload failed.</div>";
            $upload_error = true;
        }
    } elseif (empty($submission_text) && empty($submission['submission_file']) && empty($_FILES["submission_file"]["name"])) {
        $submission_message = "<div class='alert alert-warning'>Please provide either text or upload a file for your submission.</div>";
        $upload_error = true;
    }

    if (!$upload_error) {
        if ($submission) {
            // Update existing submission
            $stmt_upsert = $conn->prepare("UPDATE assignments_submissions SET submission_text = ?, submission_file = ?, submitted_at = NOW() WHERE assignment_id = ? AND student_id = ?");
            $stmt_upsert->bind_param("ssii", $submission_text, $target_file_db_path, $assignment_id_post, $student_internal_id);
        } else {
            // Insert new submission
            $stmt_upsert = $conn->prepare("INSERT INTO assignments_submissions (assignment_id, student_id, submission_text, submission_file, submitted_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_upsert->bind_param("iiss", $assignment_id_post, $student_internal_id, $submission_text, $target_file_db_path);
        }

        if ($stmt_upsert->execute()) {
            // Update assignments table with last submission info for this student (optional, for reporting)
            $stmt_assign = $conn->prepare("UPDATE assignments SET submission_file = ?, submission_text = ?, submission_date = NOW(), student_id = ? WHERE id = ?");
            $stmt_assign->bind_param("ssii", $target_file_db_path, $submission_text, $student_internal_id, $assignment_id_post);
            $stmt_assign->execute();
            $stmt_assign->close();

            $submission_message = "<div class='alert alert-success'>✅ Assignment submitted successfully!";
            if (!empty($target_file_db_path)) {
                $submission_message .= " <a href='../../" . htmlspecialchars($target_file_db_path) . "' target='_blank'>View Your File</a>";
            }
            if (!empty($submission_text)) {
                $submission_message .= "<br><strong>Your Text:</strong><br>" . nl2br(htmlspecialchars($submission_text));
            }
            $submission_message .= "</div>";

            // Re-fetch submission details to update the displayed message
            $stmt_recheck = $conn->prepare("SELECT submission_text, submission_file, submitted_at FROM assignments_submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1");
            $stmt_recheck->bind_param("ii", $assignment_id_post, $student_internal_id);
            $stmt_recheck->execute();
            $submission = $stmt_recheck->get_result()->fetch_assoc();
            $stmt_recheck->close();

        } else {
            $submission_message = "<div class='alert alert-danger'>❌ Error submitting assignment: " . htmlspecialchars($stmt_upsert->error) . "</div>";
        }
        $stmt_upsert->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow bg-white rounded">
        <h4 class="mb-4 text-primary">Submit Assignment</h4>
        <?php if ($assignment_details): ?>
            <h5 class="mb-3 text-secondary">Title: <?= htmlspecialchars($assignment_details['title']) ?></h5>
            <p class="text-muted">Due Date: <?= htmlspecialchars($assignment_details['due_date']) ?></p>
        <?php endif; ?>

        <?= $submission_message ?>
        <?php if ($assignment_id && $student_internal_id && date('Y-m-d') <= $assignment_details['due_date']): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="<?= htmlspecialchars($assignment_id) ?>">
                <div class="mb-3">
                    <label for="submission_text" class="form-label">Type your assignment below</label>
                    <textarea name="submission_text" id="submission_text" class="form-control" rows="5"><?= htmlspecialchars($submission['submission_text'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="submission_file" class="form-label">Or upload a file (optional)</label>
                    <input type="file" name="submission_file" class="form-control" accept=".pdf, .doc, .docx, .txt, .jpg, .jpeg, .png">
                    <?php if ($submission && !empty($submission['submission_file'])): ?>
                        <div class="form-text">You have already submitted a file. Uploading a new file will replace the existing one.</div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <?= ($submission) ? 'Replace Submission' : 'Submit Assignment' ?>
                </button>
            </form>
        <?php elseif (!$assignment_id && empty($submission_message)): ?>
            <div class="alert alert-warning">Please select an assignment to submit from the "My Assignments" page.</div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="view_assignments.php" class="btn btn-secondary me-2">Back to My Assignments</a>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>