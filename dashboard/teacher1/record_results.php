<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $score = $_POST['score'];
    $term = $_POST['term'];
    $session = $_POST['session'];

    $stmt = $conn->prepare("INSERT INTO results (student_id, subject, score, term, session, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssiss", $student_id, $subject, $score, $term, $session);

    if ($stmt->execute()) {
        echo "Result submitted successfully and is pending approval.";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<div class="container mt-5">
    <h4 class="text-primary mb-4 text-center">Record Student Results</h4>
    <div class="alert alert-info">Please select a student and enter the result details.</div>
<form method="POST" action="record_results.php">
    <select name="student_id" required>
        <option value="">Select Student</option>
        <?php
        $res = $conn->query("SELECT registration_id, full_name FROM students");
        while ($row = $res->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($row['registration_id']).'">'.htmlspecialchars($row['full_name']).' ('.$row['registration_id'].')</option>';
        }
        ?>
    </select>
    <input type="text" name="subject" placeholder="Subject" required>
    <input type="number" name="score" placeholder="Score" required>
    <input type="text" name="term" placeholder="Term (e.g. 1st Term)" required>
    <input type="text" name="session" placeholder="Session (e.g. 2024/2025)" required>
    <button type="submit" class="btn btn-primary btn-sm">Submit Result</button>
</form>
</div>
</body>
</html>