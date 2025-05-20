<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$profile_photo = null;

// Handle file upload if provided
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_photo']['tmp_name'];
    $fileName = basename($_FILES['profile_photo']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif'];
    $fileMime = mime_content_type($fileTmp);
    $fileSize = $_FILES['profile_photo']['size'];

    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!in_array($fileExt, $allowedExt) || !in_array($fileMime, $allowedMime)) {
        $_SESSION['error'] = "Invalid image type. Only jpg, jpeg, png, gif allowed.";
        header("Location: profile.php");
        exit;
    } elseif ($fileSize > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Image too large. Max 2MB allowed.";
        header("Location: profile.php");
        exit;
    } else {
        $newFileName = 'admin_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            $profile_photo = $newFileName;
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, profile_photo = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $phone, $profile_photo, $admin_id);
        } else {
            $_SESSION['error'] = "Failed to upload image. Check folder permissions.";
            header("Location: profile.php");
            exit;
        }
    }
} else {
    // Update without photo
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $phone, $admin_id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Profile updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update profile.";
}

header("Location: profile.php");
exit;