<?php
session_start();
include "../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

// ✅ Get teacher's data
$teacher_id = $_SESSION['user_id'];
$stmt_teacher = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$result_teacher = $stmt_teacher->get_result();
$teacher_data = $result_teacher->fetch_assoc();

// ✅ Fetch class list
// ...existing code...
// ✅ Fetch class list
$class_options = "";
$class_result = $conn->query("SELECT DISTINCT class FROM students");
while ($row = $class_result->fetch_assoc()) {
    if (!is_null($row['class'])) { // Skip null values
        $class_options .= '<option value="' . htmlspecialchars($row['class']) . '">' . htmlspecialchars($row['class']) . '</option>';
    }
}
// ...existing code...

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-10 offset-md-1 bg-white p-4 rounded shadow">
            <h4 class="text-primary mb-4 text-center">
                Welcome, <?= isset($teacher_data['name']) && $teacher_data['name'] !== null ? htmlspecialchars($teacher_data['name']) : 'Teacher' ?> (Teacher)
            </h4>
           

            <!-- 🔽 Filter & Download Section -->
            <h5 class="mb-3">Download Results</h5>
            <form method="GET" action="download_results.php" class="row g-2 mb-4">
                <div class="col-md-4">
                    <select name="class" class="form-select" required>
                        <option value="">Select Class</option>
                        <option value="basic_1">Basic 1</option>
                        

                        <?= $class_options ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <input type="date" name="end_date" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Download</button>
                </div>
            </form>

            <a href="download_results.php" class="btn btn-outline-primary mb-4">Download All Students Results (Excel)</a>

            <!-- ✅ Record Assessment -->
            <h5>Record Continuous Assessment</h5>
            <form method="POST" action="record_assessment.php" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required>
                </div>
                <div class="col-md-6">
                    <input type="number" name="assessment_score" class="form-control" placeholder="Assessment Score" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Record Assessment</button>
                </div>
            </form>

            <!-- ✅ Record Exam -->
            <h5>Record Examination Result</h5>
            <form method="POST" action="record_exam.php" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required>
                </div>
                <div class="col-md-6">
                    <input type="number" name="exam_score" class="form-control" placeholder="Exam Score" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Record Exam</button>
                </div>
            </form>

            <!-- ✅ Assign Homework -->
            <h5>Assign Homework</h5>
            <form method="POST" action="assign_homework.php" class="mb-4">
                <div class="mb-3">
                    <input type="text" name="student_id" class="form-control" placeholder="Student ID" required>
                </div>
                <div class="mb-3">
                    <textarea name="homework_details" class="form-control" rows="3" placeholder="Homework Details" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Assign Homework</button>
            </form>

            <!-- 🔒 Logout -->
            <div class="text-center">
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
