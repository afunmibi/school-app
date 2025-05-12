
<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = $_SESSION['user_id'];
    $class = $_POST['class'];
    $subject = $_POST['subject'];
    $title = $_POST['title'];
    $details = $_POST['details'];
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO assignments (title, description, teacher_id, class, subject, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $title, $details, $teacher_id, $class, $subject, $due_date);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Assignment posted successfully.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Post Assignment</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .assignment-form-card {
            max-width: 500px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0 24px rgba(44,62,80,0.10);
            padding: 2rem 2rem 1.5rem 2rem;
        }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <div class="assignment-form-card">
            <h2 class="text-center text-primary mb-3">Post Assignment</h2>
            <div class="alert alert-info text-center mb-4" role="alert">
                Please fill in the details below to post an assignment.
            </div>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-control" required>
                        <option value="">Select Class</option>
                        <option value="Basic 1">Basic 1</option>
                        <option value="Basic 2">Basic 2</option>
                        <option value="Basic 3">Basic 3</option>
                        <option value="Basic 4">Basic 4</option>
                        <option value="Basic 5">Basic 5</option>
                        <option value="Basic 6">Basic 6</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assignment Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Assignment Title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assignment Details</label>
                    <textarea name="details" class="form-control" placeholder="Assignment Details" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-info w-100">Post Assignment</button>
            </form>
        </div>
        <div class="text-center mb-3">
            <a href="./dashboard.php" class="btn btn-secondary">Back to Dashboard</a>   
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>