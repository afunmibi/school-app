<?php
session_start();
include "../../config.php";



// Only allow admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../../index.php");
    exit;
}


$id = intval($_GET['id']);

// Fetch result data
$stmt = $conn->prepare("
    SELECT r.*, s.full_name, s.class_assigned 
    FROM final_exam_results r
    LEFT JOIN students s ON r.student_id = s.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "Result not found.";
    exit;
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $assessments = intval($_POST['assessments'] ?? 0);
    $exam_scores = intval($_POST['exam_score'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');

    $stmt = $conn->prepare("UPDATE final_exam_results SET subject = ?, assessments = ?, exam_score = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sissi", $subject, $assessments, $exam_scores, $status, $id);

    if ($stmt->execute()) {
        header("Location: manage_results.php?updated=1");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Error updating result.</div>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Result</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h4 class="mb-4 text-primary">Edit Result</h4>
    <?php if ($message) echo $message; ?>
    <form method="POST">
        <div class="mb-2">
            <label>Student Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($row['full_name']) ?>" >
        </div>
        <div class="mb-2">
            <label>Student Class</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($row['class_assigned']) ?>" >
        </div>
        <div class="mb-2">
            <label>Subject</label>
            <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($row['subject']) ?>" required>
        </div>
        <div class="mb-2">
            <label>Assessment</label>
            <input type="number" name="assessments" class="form-control" value="<?= htmlspecialchars($row['assessments']) ?>" required>
        </div>
        <div class="mb-2">
            <label>Exam Score</label>
            <input type="number" name="exam_scores" class="form-control" value="<?= htmlspecialchars($row['exam_score']) ?>" required>
        </div>
        <div class="mb-2">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $row['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $row['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Update Result</button>
        <a href="manage_results.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
</body>
</html>