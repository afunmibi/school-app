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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_SESSION['user_id'];
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $title = $_POST['title'];
    $details = $_POST['details'];
    $due_date = $_POST['due_date'];

    $due_date = $_POST['due_date'];
$stmt = $conn->prepare("INSERT INTO assignments (title, description, teacher_id, student_id, due_date) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssiss", $title, $description, $teacher_id, $student_id, $due_date);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Assignment posted successfully.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Post Assignment</title>
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<body>
    <div class="container mt-5 mx-auto" >
        <h2 class="text-center">Post Assignment</h2>
        <div class="alert alert-info" role="alert">
            Please fill in the details below to post an assignment.
        </div>
    
<h2>Post Assignment</h2>
<form method="POST" action="">
    <input type="text" name="student_id" placeholder="Student ID" required><br><br>
    <input type="text" name="subject" placeholder="Subject" required><br><br>
    <input type="text" name="title" placeholder="Assignment Title" required><br><br>
    <textarea name="details" placeholder="Assignment Details" rows="5" required></textarea><br><br>
    <label>Due Date:</label>
    <input type="date" name="due_date" required><br><br>
    <label for="due_date">Due Date</label>
    <input type="date" name="due_date" class="form-control" required>
   
    <button type="submit" class="btn btn-info">Post Assignment</button>
</form>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
