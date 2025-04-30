<?php
session_start();
include "../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Redirect if not logged in as teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}
// Fetch all classes (Basic 1 to 6) for filtering
$query = "SELECT DISTINCT class FROM students";
$result = $conn->query($query);

// Show class filter, date range filter, and download link
echo '<form method="GET" action="download_results.php">
        <select name="class" class="form-control">
            <option value="">Select Class</option>';

while ($row = $result->fetch_assoc()) {
    echo '<option value="' . $row['class'] . '">' . $row['class'] . '</option>';
}

echo '      </select>
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" class="form-control mt-2">
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" class="form-control mt-2">
        <button type="submit" class="btn btn-primary mt-2">Download Results (Excel)</button>
    </form>';

// Fetch teacher's data
if (isset($_SESSION['user_id'])) {
    $teacher_id = $_SESSION['user_id'];
    $query_teacher = "SELECT * FROM users WHERE id = ?";
    $stmt_teacher = $conn->prepare($query_teacher);
    $stmt_teacher->bind_param("i", $teacher_id);
    $stmt_teacher->execute();
    $result_teacher = $stmt_teacher->get_result();
    $teacher_data = $result_teacher->fetch_assoc();

    // You can use $teacher_data here if needed
    // Example: echo "Welcome, " . $teacher_data['name'];
} else {
    // Handle the case where teacher_id is not set (shouldn't happen if login is correct)
    echo "<p class='alert alert-danger'>Error: Teacher ID not found in session.</p>";
}

echo '<a href="download_results.php" class="btn btn-primary mt-3">Download All Students Results (Excel)</a>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Teacher Dashboard</h4>

            <h5>Record Continuous Assessment</h5>
            <form method="POST" action="record_assessment.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <div class="mb-3">
                    <label for="assessment_score" class="form-label">Assessment Score</label>
                    <input type="number" class="form-control" name="assessment_score" required>
                </div>
                <button type="submit" class="btn btn-primary">Record Assessment</button>
            </form>

            <hr>

            <h5>Record Examination Result</h5>
            <form method="POST" action="record_exam.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <div class="mb-3">
                    <label for="exam_score" class="form-label">Examination Score</label>
                    <input type="number" class="form-control" name="exam_score" required>
                </div>
                <button type="submit" class="btn btn-primary">Record Exam</button>
            </form>

            <hr>

            <h5>Assign Homework</h5>
            <form method="POST" action="assign_homework.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <div class="mb-3">
                    <label for="homework_details" class="form-label">Homework Details</label>
                    <textarea class="form-control" name="homework_details" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Assign Homework</button>
            </form>

            <hr>
            <p class="text-center"><a href="../logout.php" class="btn btn-danger">Logout</a></p>
        </div>
    </div>
</body>
</html>