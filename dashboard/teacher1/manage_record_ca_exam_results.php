<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Allow only teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = "";
$teacher_class = "";
$students = [];

// Get teacher info
$stmt = $conn->prepare("SELECT full_name, email, class_assigned FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacher_class = $teacher['class_assigned'] ?? '';
$teacher_name = $teacher['full_name'] ?? '';

// Sync to teacher_classes table (optional)
if (!empty($teacher_class)) {
    $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, full_name, class_assigned)
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
    $stmt->bind_param("iss", $teacher_id, $teacher_name, $teacher_class);
    $stmt->execute();
    $stmt->close();
}

// Fetch students in class
if (!empty($teacher_class)) {
    $stmt = $conn->prepare("SELECT id, full_name, registration_id FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
    $stmt->bind_param("s", $teacher_class);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $subject = trim($_POST['subject']);
    $assessment_score = intval($_POST['assessment_score']);
    $exam_score = intval($_POST['exam_score']);
    $term = trim($_POST['term']);
    $session = trim($_POST['session']);
    $date_now = date('Y-m-d H:i:s');

    // Validation
    if (!$student_id || !$subject || $assessment_score < 0 || $assessment_score > 100 || $exam_score < 0 || $exam_score > 100 || !$term || !$session) {
        $message = "<div class='alert alert-danger'>❌ Please fill all fields correctly. Scores must be between 0 and 100.</div>";
    } else {
        // Fetch student info
        $stmt = $conn->prepare("SELECT full_name, class_assigned FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $student_name = $student_data['full_name'] ?? 'N/A';
        $student_class = $student_data['class_assigned'] ?? $teacher_class;

        // Insert/update into final_exam_results
        $final_score = $assessment_score + $exam_score;
        $stmt = $conn->prepare("INSERT INTO final_exam_results
            (student_id, full_name, class, subject, term, session, assessments, exam_score, final_score, teacher_id, status, result_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ON DUPLICATE KEY UPDATE
                assessments = VALUES(assessments),
                exam_score = VALUES(exam_score),
                final_score = VALUES(final_score),
                teacher_id = VALUES(teacher_id),
                result_date = VALUES(result_date),
                full_name = VALUES(full_name),
                class = VALUES(class),
                status = 'pending'");
        $stmt->bind_param("isssssiiiis", $student_id, $student_name, $student_class, $subject, $term, $session, $assessment_score, $exam_score, $final_score, $teacher_id, $date_now);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ Scores recorded and final result updated for <strong>$student_name</strong>.</div>";
        } else {
            $message = "<div class='alert alert-danger'>❌ Failed to update final result: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Assessment & Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
            <h4>Record Student Assessment & Exam Score</h4>
        </div>
        <div class="card-body">
            <?= $message ?>
            <form method="POST" class="row g-3">
                <div class="col-12">
                    <label for="student_id" class="form-label">Select Student (<?= htmlspecialchars($teacher_class) ?>)</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['registration_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assessment Score</label>
                    <input type="number" name="assessment_score" class="form-control" min="0" max="100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Exam Score</label>
                    <input type="number" name="exam_score" class="form-control" min="0" max="100" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Term</label>
                    <input type="text" name="term" class="form-control" placeholder="e.g. 1st Term" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Session</label>
                    <input type="text" name="session" class="form-control" placeholder="e.g. 2023/2024" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100">Submit Scores</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>