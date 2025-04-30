<?php
session_start();
include "../config.php";

// Redirect if not logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $student_id = $_POST['student_id'];
    $exam_score = $_POST['exam_score'];

    // Validate input
    if (empty($student_id) || empty($exam_score)) {
        echo "Please fill in all fields.";
        exit;
    }

    // Insert into database
    $query = "INSERT INTO exam_results (student_id, exam_score, teacher_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $student_id, $exam_score, $_SESSION['teacher_id']);

    if ($stmt->execute()) {
        echo "Exam result recorded successfully.";
    } else {
        echo "Error recording exam result.";
    }
}
?>
