<?php
session_start();
include "../../../config.php";

// Use unique_id from session for lookup
$unique_id = $_SESSION['student_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data using unique_id
$query = "SELECT * FROM students WHERE unique_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}
// Check if PDF exists
$pdf_path = '../pdfs/student_' . $student_data['unique_id'] . '.pdf';
?>