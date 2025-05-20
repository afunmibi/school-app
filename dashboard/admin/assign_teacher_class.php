<?php
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'], $_POST['class_assigned'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $class = trim($_POST['class_assigned']);
} else {
    header("Location: dashboard.php?msg=Invalid+request");
    exit;
}

// 1. Update the teacher's assigned class in users table
$stmt = $conn->prepare("UPDATE users SET class_assigned = ? WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("si", $class, $teacher_id);
$stmt->execute();
$stmt->close();

// 2. Update the teacher's assigned class in teachers table
$stmt2 = $conn->prepare("UPDATE teachers SET class_assigned = ? WHERE teacher_id = ?");
$stmt2->bind_param("si", $class, $teacher_id);
$stmt2->execute();
$stmt2->close();

// 3. Insert into teacher_classes table if not already assigned
$stmt3 = $conn->prepare("SELECT id FROM teacher_classes WHERE teacher_id = ? AND class_assigned = ?");
$stmt3->bind_param("is", $teacher_id, $class);
$stmt3->execute();
$stmt3->store_result();
if ($stmt3->num_rows === 0) {
    // Get teacher's full name for insert
    $full_name = '';
    $get_name = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $get_name->bind_param("i", $teacher_id);
    $get_name->execute();
    $get_name->bind_result($full_name);
    $get_name->fetch();
    $get_name->close();

    $stmt3->close();
    $stmt3i = $conn->prepare("INSERT INTO teacher_classes (teacher_id, full_name, class_assigned) VALUES (?, ?, ?)");
    $stmt3i->bind_param("iss", $teacher_id, $full_name, $class);
    $stmt3i->execute();
    $stmt3i->close();
} else {
    $stmt3->close();
}

// 4. Update the teacher's profile (teacher_profile table)
$stmt4 = $conn->prepare("UPDATE teacher_profile SET class_assigned = ? WHERE teacher_id = ?");
$stmt4->bind_param("si", $class, $teacher_id);
$stmt4->execute();
$stmt4->close();

echo "<script>alert('Teacher assigned to class successfully.'); window.location.href='dashboard.php';</script>";
exit;