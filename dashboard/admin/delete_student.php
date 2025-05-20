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

// Optional: Delete associated files
$stmt = $conn->prepare("SELECT passport_photo, birth_certificate, testimonial FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($passport, $birth, $testi);
if ($stmt->fetch()) {
    if ($passport && file_exists("../../uploads/passports/$passport")) unlink("../../uploads/passports/$passport");
    if ($birth && file_exists("../../uploads/birth_certificates/$birth")) unlink("../../uploads/birth_certificates/$birth");
    if ($testi && file_exists("../../uploads/testimonials/$testi")) unlink("../../uploads/testimonials/$testi");
}
$stmt->close();

// Delete student
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: add_student.php?deleted=1");
    exit;
} else {
    echo "Error deleting student.";
}
?>