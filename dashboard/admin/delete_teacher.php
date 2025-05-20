<?php
session_start();
include "../../config.php";

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

// Optional: Delete teacher's profile photo
$stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($photo);
if ($stmt->fetch() && $photo && file_exists("../../uploads/teachers/$photo")) {
    unlink("../../uploads/teachers/$photo");
}
$stmt->close();

// Optional: Delete from related tables
$conn->query("DELETE FROM teacher_profile WHERE teacher_id = $id");
$conn->query("DELETE FROM teacher_classes WHERE teacher_id = $id");

// Delete teacher
$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: dashboard.php?deleted=1");
    exit;
} else {
    echo "Error deleting teacher.";
}
?>