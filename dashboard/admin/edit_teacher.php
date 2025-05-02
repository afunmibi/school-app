<?php
include "../../config.php";

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

// Fetch teacher
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
$class_stmt = $conn->prepare("SELECT assigned_class FROM teacher_classes WHERE teacher_id = ?");
$class_stmt->bind_param("i", $id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();
$teacher_class = $class_result->fetch_assoc();
$assigned_class = $teacher_class['assigned_class'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $new_class = trim($_POST['assigned_class']);

    // Update teacher info
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $username, $id);
    $stmt->execute();

    // Update or insert assigned class
    $check_stmt = $conn->prepare("SELECT id FROM teacher_classes WHERE teacher_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $update_class = $conn->prepare("UPDATE teacher_classes SET assigned_class = ? WHERE teacher_id = ?");
        $update_class->bind_param("si", $new_class, $id);
        $update_class->execute();
    } else {
        $insert_class = $conn->prepare("INSERT INTO teacher_classes (teacher_id, assigned_class) VALUES (?, ?)");
        $insert_class->bind_param("is", $id, $new_class);
        $insert_class->execute();
    }

    header("Location: dashboard.php?updated=1");
    exit;
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
        <form method="POST">
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
            <button type="submit" class="btn btn-primary">Update Teacher</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
