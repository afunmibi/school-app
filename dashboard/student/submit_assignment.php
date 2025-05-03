<?php
session_start();
include "../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only students allowed
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];
    $student_id = $_SESSION['student_id'];

    // Handle file upload
    $target_dir = "uploads/";
    $filename = basename($_FILES["submission_file"]["name"]);
    $target_file = $target_dir . time() . "_" . $filename;

    if (move_uploaded_file($_FILES["submission_file"]["tmp_name"], $target_file)) {
        // Update the assignment record
        $stmt = $conn->prepare("UPDATE assignments SET submission_file = ? WHERE id = ? AND student_id = ?");
        $stmt->bind_param("sii", $target_file, $assignment_id, $student_id);
        $stmt->execute();

        echo "✅ Assignment submitted successfully.";
    } else {
        echo "❌ Error uploading the file.";
    }
}
$due_check = $conn->prepare("SELECT due_date FROM assignments WHERE id = ? AND student_id = ?");
$due_check->bind_param("ii", $assignment_id, $student_id);
$due_check->execute();
$due_result = $due_check->get_result();
$assignment = $due_result->fetch_assoc();

if (!$assignment) {
    echo "Assignment not found.";
    exit;
}

$today = date('Y-m-d');
if ($today > $assignment['due_date']) {
    echo "❌ Deadline has passed. You can no longer submit this assignment.";
    exit;
}
?>
