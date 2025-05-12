<?php
session_start();
include "../../config.php";

// Restrict access to only logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Fetch teacher info

// Fetch teacher info
$stmt = $conn->prepare("SELECT full_name, email, phone, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $upload_file_name = $teacher['profile_photo'] ?? '';

    // ... (image upload code unchanged) ...

    // Update user data with profile photo path
    $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, profile_photo = ? WHERE id = ?");
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $update_stmt->bind_param("ssssi", $name, $email, $phone, $upload_file_name, $teacher_id);

    if ($update_stmt->execute()) {
        echo "<div class='alert alert-success'>Profile updated.</div>";
        $teacher['full_name'] = $name;
        $teacher['email'] = $email;
        $teacher['phone'] = $phone;
        $teacher['profile_photo'] = $upload_file_name;
    } else {
        echo "<div class='alert alert-danger'>Update failed.</div>";
    }
}
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
        <img src="../uploads/<?= htmlspecialchars($teacher['profile_photo']) ?>" width="100" class="rounded">
    </div>
<?php endif; ?>

<?php if (!empty($teacher['profile_photo'])): ?>
    <div class="mb-3">
        <img src="../uploads/<?= htmlspecialchars($teacher['profile_photo']) ?>" width="100" class="rounded">
    </div>
<?php endif; ?>

<button type="submit" class="btn btn-primary">Update Profile</button>
<a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
</form>

            </form>
        </div>
    </div>
</body>
</html>
