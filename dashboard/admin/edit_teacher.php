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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $username, $id);
    if ($stmt->execute()) {
        header("Location: dashboard.php?updated=1");
        exit;
    } else {
        echo "Error updating record.";
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
            <button type="submit" class="btn btn-primary">Update Teacher</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
