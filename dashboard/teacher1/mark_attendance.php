<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$marked_ids = $_POST['attendance'] ?? []; // Checked students
$today = date('Y-m-d');

// ✅ Fetch teacher's class for fallback
$stmt = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($class_assigned);
$stmt->fetch();
$stmt->close();

// ✅ Fetch all students in that class
$all_students = [];
$stmt = $conn->prepare("SELECT student_id FROM students WHERE class_assigned = ?");
$stmt->bind_param("s", $class_assigned);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_students[] = $row['student_id'];
}
$stmt->close();

// ✅ Loop through all students and insert attendance
$stmt = $conn->prepare("
    INSERT INTO attendance (student_id, date, status, class, teacher_id) 
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
");

foreach ($all_students as $sid) {
    $status = in_array($sid, $marked_ids) ? 'present' : 'absent';
    $stmt->bind_param("ssssi", $sid, $today, $status, $class_assigned, $teacher_id);
    $stmt->execute();
}
$stmt->close();

header("Location: dashboard.php?attendance=1");
exit;
?>

