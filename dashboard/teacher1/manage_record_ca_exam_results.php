<?php
session_start();
include "../../config.php"; // Ensure this path is correct
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Allow only teachers or admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}
$teacher_id = $_SESSION['user_id'];
$student_id = $_SESSION['student_unique_id'] ?? null;

$message = "";
$teacher_class = "";
$students = [];

// Get teacher info (name and assigned class)
// Using prepared statements for security
$stmt = $conn->prepare("SELECT full_name, class_assigned FROM users WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacher_class = $teacher['class_assigned'] ?? '';
$teacher_name = $teacher['full_name'] ?? '';

// Fetch students in teacher's class
if (!empty($teacher_class)) {
    $stmt = $conn->prepare("SELECT student_numeric_id, full_name, unique_id FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("s", $teacher_class);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
// fetch students in teacher's class


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $student_numeric_id = isset($_POST['student_id']) && !empty($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $subject = trim($_POST['subject'] ?? '');
    $assessment_score = isset($_POST['assessment_score']) ? intval($_POST['assessment_score']) : -1;
    $exam_score = isset($_POST['exam_score']) ? intval($_POST['exam_score']) : -1;
    $term = trim($_POST['term'] ?? '');
    $session_val = trim($_POST['session'] ?? '');
    $date_now = date('Y-m-d H:i:s');

    // Validate inputs
    if ($student_numeric_id <= 0) {
        $message = "<div class='alert alert-danger'>❌ Please select a valid student.</div>";
    } elseif ($subject === '') {
        $message = "<div class='alert alert-danger'>❌ Please enter the subject.</div>";
    } elseif ($assessment_score < 0 || $assessment_score > 100) {
        $message = "<div class='alert alert-danger'>❌ Assessment score must be between 0 and 100.</div>";
    } elseif ($exam_score < 0 || $exam_score > 100) {
        $message = "<div class='alert alert-danger'>❌ Exam score must be between 0 and 100.</div>";
    } elseif ($term === '') {
        $message = "<div class='alert alert-danger'>❌ Please enter the term.</div>";
    } elseif ($session_val === '') {
        $message = "<div class='alert alert-danger'>❌ Please enter the session.</div>";
    } else {
        // Fetch student info by student_numeric_id
      $stmt = $conn->prepare("SELECT full_name, class_assigned, unique_id FROM students WHERE student_numeric_id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("i", $student_numeric_id);
        $stmt->execute();
        $student_data = $stmt->get_result()->fetch_assoc();
        // var_dump($student_data);
        // echo "Unique ID: " . htmlspecialchars($student_data['unique_id']) . "<br>";
        $stmt->close();

        if (!$student_data) {
            $message = "<div class='alert alert-danger'>❌ Student not found. Please select a valid student.</div>";
        } else {
            $student_name = $student_data['full_name'];
            $student_class = $student_data['class_assigned'];
            $unique_id = $student_data['unique_id'];
            $final_score = $assessment_score + $exam_score;

            // Insert or update final_exam_results record using ON DUPLICATE KEY UPDATE
            // This assumes you have a UNIQUE constraint on (student_numeric_id, subject, term, session)
            $stmt = $conn->prepare("INSERT INTO final_exam_results
                (student_numeric_id, unique_id, full_name, subject, term, session, assessments, exam_score, final_score, teacher_id, status, result_date, class_assigned)
                VALUES (?, ?, ?,  ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
                ON DUPLICATE KEY UPDATE
                    assessments = VALUES(assessments),
                    exam_score = VALUES(exam_score),
                    final_score = VALUES(final_score),
                    teacher_id = VALUES(teacher_id),
                    result_date = VALUES(result_date),
                    full_name = VALUES(full_name),
                    term = VALUES(term),
                    class_assigned = VALUES(class_assigned),
                    unique_id = VALUES(unique_id),
                    status = 'pending'
            ");

            if ($stmt === false) {
                die("Prepare failed: " . htmlspecialchars($conn->error));
            }

            $stmt->bind_param("issssssiiiss",
                $student_numeric_id,
                $unique_id,
                $student_name,
                // $student_class, // This is 'class' column in final_exam_results
                $subject,
                $term,
                $session_val,
                $assessment_score,
                $exam_score,
                $final_score,
                $teacher_id,
                $date_now,
                $student_class // This is 'class_assigned' column in final_exam_results
            );
error_log("Unique ID before execute: " . $unique_id);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>✅ Scores recorded successfully for <strong>" . htmlspecialchars($student_name) . "</strong>.</div>";
                // Clear form fields after successful submission
                $_POST = [];
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to save result: " . htmlspecialchars($stmt->error) . "</div>";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Record Scores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h4>Record CA and Exam Scores</h4>
        </div>
        <div class="card-body">
            <?= $message ?>
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    No students found for your assigned class (<?= htmlspecialchars($teacher_class) ?>). Please ensure students are assigned to this class.
                </div>
            <?php else: ?>
                <form method="POST" class="row g-3" > <div class="col-12">
                        <label for="student_id" class="form-label">Select Student (<?= htmlspecialchars($teacher_class) ?>)</label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= (int)$student['student_numeric_id'] ?>"
                                    <?= (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_numeric_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['unique_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="assessment_score" class="form-label">Assessment Score</label>
                        <input type="number" name="assessment_score" id="assessment_score" class="form-control" min="0" max="30" required value="<?= htmlspecialchars($_POST['assessment_score'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="exam_score" class="form-label">Exam Score</label>
                        <input type="number" name="exam_score" id="exam_score" class="form-control" min="0" max="70" required value="<?= htmlspecialchars($_POST['exam_score'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="term" class="form-label">Term</label>
                        <input type="text" name="term" id="term" class="form-control" placeholder="e.g. 1st Term" required value="<?=  htmlspecialchars($_POST['term'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="session" class="form-label">Session</label>
                        <input type="text" name="session" id="session" class="form-control" placeholder="e.g. 2023/2024" required value="<?= htmlspecialchars($_POST['session'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100">Submit Scores</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>