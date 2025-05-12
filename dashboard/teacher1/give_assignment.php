<?php
session_start();
include "../../config.php";

// Redirect if not logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $student_id = $_POST['student_id'];
    $homework_details = $_POST['homework_details'];

    // Validate input
    if (empty($student_id) || empty($homework_details)) {
        echo "Please fill in all fields.";
        exit;
    }

    // Insert into database
    $query = "INSERT INTO homework (student_id, homework_details, teacher_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $student_id, $homework_details, $_SESSION['teacher_id']);

    if ($stmt->execute()) {
        echo "Homework assigned successfully.";
    } else {
        echo "Error assigning homework.";
    }
}
?>
