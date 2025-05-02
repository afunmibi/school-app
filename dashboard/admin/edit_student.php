<?php
include "../../config.php";

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);

// Fetch student
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("UPDATE students SET full_name = ?, email_address = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $id);
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
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 offset-md-3 bg-white p-4 shadow rounded">
        <h4 class="mb-3 text-primary">Edit Student</h4>
        <form method="POST">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($student['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email_address']) ?>" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Student</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>

