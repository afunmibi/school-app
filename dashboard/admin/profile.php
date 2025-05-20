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

// Fetch current admin profile data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .profile-card {
            max-width: 500px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0 16px rgba(44,62,80,0.08);
            padding: 2rem 1.5rem;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 2px solid #2563eb;
        }
        @media (max-width: 575.98px) {
            .profile-card { padding: 1rem 0.5rem; }
            h4 { font-size: 1.2rem; }
            .profile-avatar { width: 70px; height: 70px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="profile-card text-center">
        <h4 class="text-primary mb-4">Admin Profile</h4>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Display Profile Photo -->
        <div class="mb-3">
            <?php
                $photo = !empty($admin_data['profile_photo']) ? $admin_data['profile_photo'] : 'default.png';
            ?>
            <img src="../uploads/<?= htmlspecialchars($photo) ?>"
                 alt="Admin Profile Photo"
                 class="profile-avatar img-fluid rounded-circle">
        </div>

        <!-- Profile Form -->
        <form action="update_admin_profile.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3 text-start">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" class="form-control"
                       value="<?= htmlspecialchars($admin_data['full_name'] ?? '') ?>" required>
            </div>
            <div class="mb-3 text-start">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" name="phone" id="phone" class="form-control"
                       value="<?= htmlspecialchars($admin_data['phone'] ?? '') ?>" required>
            </div>
            <div class="mb-3 text-start">
                <label for="profile_photo" class="form-label">Profile Photo</label>
                <input type="file" name="profile_photo" id="profile_photo" class="form-control" accept="image/*">
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>