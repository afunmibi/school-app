<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

// Get teacher's assigned class
$teacher_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT class_assigned FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($class_assigned);
$stmt->fetch();
$stmt->close();

$message = "";

// Fetch all students in the teacher's class for dropdown
$students = [];
$res = $conn->prepare("SELECT registration_id, full_name FROM students WHERE class_assigned = ?");
$res->bind_param("s", $class_assigned);
$res->execute();
$result_students = $res->get_result();
while ($row = $result_students->fetch_assoc()) {
    $students[] = $row;
}
$res->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $subject = trim($_POST['subject']);
    $score = trim($_POST['score']);
    $term = trim($_POST['term']);
    $session_val = trim($_POST['session']);

    // Fetch full_name for the selected student_id
    $stmt_name = $conn->prepare("SELECT full_name FROM students WHERE registration_id = ?");
    $stmt_name->bind_param("s", $student_id);
    $stmt_name->execute();
    $stmt_name->bind_result($full_name);
    $stmt_name->fetch();
    $stmt_name->close();

    if (!$full_name) {
        $message = "<div class='alert alert-danger'>Invalid student selected.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO results (full_name, student_id, class_assigned, subject, score, term, session, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sssssss", $full_name, $student_id, $class_assigned, $subject, $score, $term, $session_val);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Result submitted successfully and is pending approval.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Student Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Record Student Results</h4>
            </div>
            <div class="card-body">
                <?= $message ?>
                <div class="alert alert-info">Select a student from your class and enter the result details.</div>
                <form method="POST" action="record_results.php" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars($student['registration_id']) ?>">
                                    <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['registration_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Score</label>
                        <input type="number" name="score" class="form-control" placeholder="Score" min="0" max="100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Term</label>
                        <input type="text" name="term" class="form-control" placeholder="Term (e.g. 1st Term)" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Session</label>
                        <input type="text" name="session" class="form-control" placeholder="Session (e.g. 2024/2025)" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Submit Result</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>