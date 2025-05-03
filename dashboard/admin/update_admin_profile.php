<?php
session_start();
include "../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$profile_photo = null;

// ✅ Handle file upload if provided
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_photo']['tmp_name'];
    $fileName = basename($_FILES['profile_photo']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowedExt)) {
        $newFileName = 'admin_' . time() . '.' . $fileExt;
        $uploadDir = '../uploads/';
        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            $profile_photo = $newFileName;

            // ✅ Update with new photo
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, profile_photo = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $phone, $profile_photo, $admin_id);
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: admin_profile.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid image type.";
        header("Location: admin_profile.php");
        exit;
    }
} else {
    // ✅ Update without photo
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $phone, $admin_id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Profile updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update profile.";
}

header("Location: admin_profile.php");

 if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  
exit;

