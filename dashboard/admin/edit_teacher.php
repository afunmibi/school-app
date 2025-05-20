<?php
include "../../config.php";

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

// Fetch teacher from users table
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    echo "Teacher not found.";
    exit;
}

// Fetch assigned class (if any)
$class_stmt = $conn->prepare("SELECT class_assigned FROM teacher_classes WHERE teacher_id = ?");
$class_stmt->bind_param("i", $id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$teacher_class = $class_result->fetch_assoc();
$assigned_class = $teacher_class['class_assigned'] ?? '';

// Fetch teacher_profile info
$profile_stmt = $conn->prepare("SELECT qualification, phone_number, passport_photo FROM teacher_profile WHERE teacher_id = ?");
$profile_stmt->bind_param("i", $id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();
$qualification = $profile['qualification'] ?? '';
$phone_number = $profile['phone_number'] ?? '';
$passport_photo = $profile['passport_photo'] ?? '';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $new_class = trim($_POST['assigned_class']);
    $qualification = trim($_POST['qualification']);
    $phone_number = trim($_POST['phone_number']);
    $new_password = trim($_POST['password']);

    // Handle passport photo upload if provided
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['passport_photo']['tmp_name']);
        $file_size = $_FILES['passport_photo']['size'];
        if (!in_array($file_type, $allowed_types)) {
            $message = "<div class='alert alert-danger'>Invalid file type. Only JPG, PNG, and GIF allowed.</div>";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $message = "<div class='alert alert-danger'>File too large. Max 2MB allowed.</div>";
        } else {
            $upload_dir = "../../uploads/teachers/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ext = pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION);
            $passport_photo = uniqid('teacher_', true) . '.' . $ext;
            move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $passport_photo);
        }
    }

    if (empty($message)) {
        // Update users table (also update class_assigned and password if provided)
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_user = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, class_assigned = ?, password = ? WHERE id = ?");
            $stmt_user->bind_param("sssssi", $name, $email, $username, $new_class, $hashed_password, $id);
        } else {
            $stmt_user = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, class_assigned = ? WHERE id = ?");
            $stmt_user->bind_param("ssssi", $name, $email, $username, $new_class, $id);
        }
        $stmt_user->execute();

        // Update teachers table (also update class_assigned)
        $stmt_teacher = $conn->prepare("UPDATE teachers SET full_name = ?, email = ?, class_assigned = ? WHERE teacher_id = ?");
        $stmt_teacher->bind_param("sssi", $name, $email, $new_class, $id);
        $stmt_teacher->execute();

        // Update or insert assigned class in teacher_classes
        $check_stmt = $conn->prepare("SELECT id FROM teacher_classes WHERE teacher_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $update_class = $conn->prepare("UPDATE teacher_classes SET class_assigned = ? WHERE teacher_id = ?");
            $update_class->bind_param("si", $new_class, $id);
            $update_class->execute();
        } else {
            $insert_class = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_assigned) VALUES (?, ?)");
            $insert_class->bind_param("is", $id, $new_class);
            $insert_class->execute();
        }

        // Update or insert teacher_profile (with class_assigned)
        $check_profile = $conn->prepare("SELECT id FROM teacher_profile WHERE teacher_id = ?");
        $check_profile->bind_param("i", $id);
        $check_profile->execute();
        $profile_result = $check_profile->get_result();
        if ($profile_result->num_rows > 0) {
            $update_profile = $conn->prepare("UPDATE teacher_profile SET qualification = ?, phone_number = ?, passport_photo = ?, class_assigned = ? WHERE teacher_id = ?");
            $update_profile->bind_param("ssssi", $qualification, $phone_number, $passport_photo, $new_class, $id);
            $update_profile->execute();
        } else {
            $insert_profile = $conn->prepare("INSERT INTO teacher_profile (teacher_id, qualification, phone_number, passport_photo, class_assigned) VALUES (?, ?, ?, ?, ?)");
            $insert_profile->bind_param("issss", $id, $qualification, $phone_number, $passport_photo, $new_class);
            $insert_profile->execute();
        }

        header("Location: dashboard.php?updated=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 offset-md-3 bg-white p-4 shadow rounded">
        <h4 class="mb-3 text-primary">Edit Teacher</h4>
        <?php if (!empty($message)) echo $message; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($teacher['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Assign Class</label>
                <select name="assigned_class" class="form-select" required>
                    <option value="">-- Select Class --</option>
                    <?php
                    $classes = ['Basic 1', 'Basic 2', 'Basic 3', 'Basic 4', 'Basic 5', 'Basic 6'];
                    foreach ($classes as $class) {
                        $selected = ($assigned_class == $class) ? 'selected' : '';
                        echo "<option value=\"$class\" $selected>$class</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Qualification</label>
                <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($qualification) ?>">
            </div>
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($phone_number) ?>">
            </div>
            <div class="mb-3">
                <label>Passport Photo</label>
                <?php if ($passport_photo): ?>
                    <div class="mb-2">
                        <img src="../../uploads/teachers/<?= htmlspecialchars($passport_photo) ?>" alt="Passport" style="max-width:80px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="passport_photo" class="form-control">
            </div>
            <div class="mb-3">
                <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Update Teacher</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>