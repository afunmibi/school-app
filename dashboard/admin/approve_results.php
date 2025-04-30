<?php
session_start();
include "../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $student_id = $_POST['student_id'];
    
    // Validate input
    if (empty($student_id)) {
        echo "Please enter a student ID.";
        exit;
    }

    // Approve the results by updating the status
    $query = "UPDATE exam_results SET status = 'approved' WHERE student_id = ? AND status IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);

    if ($stmt->execute()) {
        echo "Results approved successfully.";
    } else {
        echo "Error approving results.";
    }
}
?>
