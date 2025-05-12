<?php
// filepath: c:\xampp\htdocs\PHP-Projects-Here\school-app\dashboard\student\dashboard.php
session_start();
include "../../config.php";
// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../../../index.php");
    exit;
}

$unique_id = $_SESSION['student_id'];

// Fetch student record
$stmt = $conn->prepare("SELECT * FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "<div class='alert alert-danger mt-5 text-center'>Student record not found for ID: <strong>" . htmlspecialchars($unique_id) . "</strong>. Please contact admin.</div>";
    exit;
}

// Fetch class teacher info using class_assigned
$teacher_stmt = $conn->prepare("SELECT full_name, profile_photo FROM users WHERE role='teacher' AND class_assigned = ? LIMIT 1");
$teacher_stmt->bind_param("s", $student['class_assigned']);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();
$teacher = $teacher_result->fetch_assoc();
$teacher_stmt->close();
$conn->close();

// Teacher photo logic
$teacher_photo = !empty($teacher['profile_photo'])
    ? "../../uploads/" . htmlspecialchars($teacher['profile_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($teacher['full_name'] ?? 'Teacher') . "&background=2563eb&color=fff";

// Student photo logic
$student_avatar = !empty($student['passport_photo'])
    ? "../uploads/" . htmlspecialchars($student['passport_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($student['full_name']) . "&background=2563eb&color=fff";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .student-card {
            max-width: 500px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0 16px rgba(44,62,80,0.08);
            padding: 2rem 1.5rem;
        }
        .student-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 2px solid #2563eb;
        }
        .teacher-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
            border: 2px solid #1e293b;
        }
        @media (max-width: 575.98px) {
            .student-card { padding: 1rem 0.5rem; }
            h4, h5 { font-size: 1.1rem; }
            .student-avatar { width: 50px; height: 50px; }
            .teacher-avatar { width: 40px; height: 40px; }
            .btn { font-size: 0.95rem; padding: 0.75rem 0.5rem; }
        }
        .btn-warning, .btn-success, .btn-dark, .btn-info, .btn-primary, .btn-danger {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="student-card text-center">
        <img src="<?= $student_avatar ?>" alt="Student" class="student-avatar">
        <h4 class="text-primary mb-1">Welcome, <?= htmlspecialchars($student['full_name']) ?></h4>
        <p class="mb-2 text-muted"><?= htmlspecialchars($student['unique_id']) ?></p>
        <hr>
        <div class="row mb-3">
            <div class="col-6 text-start"><strong>Class:</strong></div>
            <div class="col-6 text-end"><?= htmlspecialchars($student['class_assigned'] ?? '-') ?></div>
            <div class="col-6 text-start"><strong>Status:</strong></div>
            <div class="col-6 text-end"><?= htmlspecialchars($student['status'] ?? '-') ?></div>
        </div>
        <hr>
        <div class="mb-3">
            <h5 class="mb-2">Class Teacher</h5>
            <?php if ($teacher): ?>
                <img src="<?= $teacher_photo ?>" alt="Teacher" class="teacher-avatar">
                <div><?= htmlspecialchars($teacher['full_name']) ?></div>
            <?php else: ?>
                <div class="text-danger">No teacher assigned to your class yet.</div>
            <?php endif; ?>
        </div>
        <hr>
        <h5 class="mb-3">Quick Access</h5>
        <div class="d-grid gap-2 mb-2">
            <a href="registration/full_registration.php" class="btn btn-warning">Complete Full Registration</a>
            <a href="view_results.php" class="btn btn-success">View Results</a>
            <a href="generate_result_pdf.php" class="btn btn-dark">Download Result (PDF)</a>
            <a href="view_assignments.php" class="btn btn-info">View Assignments</a>
            <a href="submit_assignment.php" class="btn btn-primary">Submit Assignment</a>
        </div>
        <hr>
        <a href="../../logout.php" class="btn btn-danger w-100">Logout</a>
    </div>
</div>
</body>
</html>