<?php
session_start();
include "../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $homework_details = trim($_POST['homework_details']);
    $teacher_id = $_SESSION['user_id'];

    // Validate student ID
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Assign homework
        $stmt_insert = $conn->prepare("INSERT INTO homework (student_id, homework_text, teacher_id, assigned_date) VALUES (?, ?, ?, NOW())");
        $stmt_insert->bind_param("ssi", $student_id, $homework_details, $teacher_id);
        if ($stmt_insert->execute()) {
            echo "Homework assigned successfully. <a href='dashboard.php'>Go Back</a>";
        } else {
            echo "Error assigning homework.";
        }
    } else {
        echo "Invalid Student ID. <a href='dashboard.php'>Go Back</a>";
    }
}
?>
