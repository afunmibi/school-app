<?php

session_start();
include "../../config.php";

// Only allow admin or teacher
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

// Delete result
$stmt = $conn->prepare("DELETE FROM final_exam_results WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: manage_results.php?deleted=1");
    exit;
} else {
    echo "Error deleting result.";
}
$stmt->close();
$conn->close();
?>