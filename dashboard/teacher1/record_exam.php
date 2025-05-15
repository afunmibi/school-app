<?php
session_start();
include "../../config.php";

// Ensure teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$message = "";

// Get the correct teachers.id for the logged-in teacher
$teacher_user_id = $_SESSION['user_id'];
$teacher_id = $teacher_user_id;
$stmt_tid = $conn->prepare("SELECT id FROM teachers WHERE teacher_id = ?");
$stmt_tid->bind_param("i", $teacher_user_id);
$stmt_tid->execute();
$stmt_tid->bind_result($teacher_id);
$stmt_tid->fetch();
$stmt_tid->close();

if (!$teacher_id) {
    die('<div class="alert alert-danger">Teacher profile not found. Please contact admin.</div>');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $exam_score = $_POST['exam_score'] ?? '';
    $status = 'pending';
    $class = $_POST['student_class'] ?? '';
    $result_date = date('Y-m-d');

    // Validate required fields
    if (!$student_id || !$full_name || !$class || !$subject) {
        $message = '<div class="alert alert-danger">Please select a student and ensure all fields are filled.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO exam_results (student_id, full_name, subject, exam_score, teacher_id, status, class, result_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisss", $student_id, $full_name, $subject, $exam_score, $teacher_id, $status, $class, $result_date);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Exam result submitted successfully and is pending approval.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}

// Fetch students for dropdown and JS
$students = [];
$sql = "SELECT id, full_name, class_assigned FROM students ORDER BY full_name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Exam Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4>Record Exam Result</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($message)) echo $message; ?>
            <form action="record_exam.php" method="POST">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Select Student</label>
                    <select name="student_id" id="student_id" class="form-select" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>"
                                    data-name="<?= htmlspecialchars($student['full_name']) ?>"
                                    data-class="<?= htmlspecialchars($student['class_assigned']) ?>">
                                <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['class_assigned']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" readonly required>
                </div>

                <div class="mb-3">
                    <label for="student_class" class="form-label">Class</label>
                    <input type="text" id="student_class" name="student_class" class="form-control" readonly required>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter Subject" required>
                </div>

                <div class="mb-3">
                    <label for="exam_score" class="form-label">Exam Score</label>
                    <input type="number" name="exam_score" id="exam_score" class="form-control" min="0" max="100" required>
                </div>

                <button type="submit" class="btn btn-success">Submit Result</button>
            </form>
        </div>
    </div>
</div>

<script>
    const studentSelect = document.getElementById('student_id');
    const nameField = document.getElementById('full_name');
    const classField = document.getElementById('student_class');

    studentSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        nameField.value = selected.getAttribute('data-name') || '';
        classField.value = selected.getAttribute('data-class') || '';
    });
</script>
</body>
</html>