<?php
session_start();
include "../../config.php";

// Restrict access to logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT full_name, email, phone, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Handle profile update including file upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $upload_file_name = $teacher['profile_photo'] ?? ''; // Keep existing file if no new file uploaded

    // Handle file upload if a new file is submitted
    if (!empty($_FILES["profile_photo"]["name"])) {
        $target_dir = "../../uploads/";
        $target_file = $target_dir . basename($_FILES["profile_photo"]["name"]);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Validate file type (Allow only images)
        if (!in_array($file_type, ["jpg", "jpeg", "png", "gif"])) {
            echo "<div class='alert alert-danger'>❌ Invalid file type. Allowed: JPG, JPEG, PNG, GIF</div>";
        } elseif ($_FILES["profile_photo"]["size"] > 5000000) { // Limit: 5MB
            echo "<div class='alert alert-danger'>❌ File size too large. Max: 5MB</div>";
        } elseif (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
            $upload_file_name = basename($_FILES["profile_photo"]["name"]);
        } else {
            echo "<div class='alert alert-danger'>❌ File upload failed.</div>";
        }
    }

    // Update user profile details in the database
    $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ? WHERE id = ?");
    $update_stmt->bind_param("ssssi", $name, $email, $phone, $upload_file_name, $teacher_id);

    if ($update_stmt->execute()) {
        echo "<div class='alert alert-success'>✅ Profile updated successfully.</div>";
        $teacher['full_name'] = $name;
        $teacher['email'] = $email;
        $teacher['phone'] = $phone;
        $teacher['profile_photo'] = $upload_file_name;
    } else {
        echo "<div class='alert alert-danger'>❌ Update failed.</div>";
    }

    // Close statements
    $stmt->close();
    $update_stmt->close();
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Teacher Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 bg-white p-4 rounded shadow">
            <h4 class="mb-4">My Profile</h4>
            <form method="POST" action="" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Photo</label>
                    <input type="file" name="profile_photo" class="form-control">
                </div>

                <?php if (!empty($teacher['profile_photo'])): ?>
                    <div class="mb-3">
                        <img src="../../uploads/<?= htmlspecialchars($teacher['profile_photo']) ?>" width="100" class="rounded">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</body>
</html>
