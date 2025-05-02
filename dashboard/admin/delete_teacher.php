<?php
include "../../config.php";

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

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
