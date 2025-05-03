<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../index.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Get student info from the main students table
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
        }
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
        @media (max-width: 575.98px) {
            .student-card {
                padding: 1rem 0.5rem;
            }
            h4, h5 {
                font-size: 1.1rem;
            }
        }
        @media (max-width: 575.98px) {
    .student-card {
        padding: 1rem 0.5rem;
    }
    h4, h5 {
        font-size: 1.1rem;
    }
    .student-avatar {
        width: 50px;
        height: 50px;
    }
    .btn {
        font-size: 0.95rem;
        padding: 0.75rem 0.5rem;
    }
}
        .btn-warning, .btn-success, .btn-dark, .btn-info, .btn-primary {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    
        .btn-danger {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="student-card text-center">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($student['full_name'] ?? 'Student') ?>&background=2563eb&color=fff" alt="Student" class="student-avatar">
        <h4 class="text-primary mb-1">Welcome, <?= htmlspecialchars($student['full_name'] ?? 'Student') ?></h4>
        <p class="mb-2 text-muted"><?= htmlspecialchars($student['student_id']) ?></p>
        <hr>
        <div class="row mb-3">
            <div class="col-6 text-start"><strong>Class:</strong></div>
            <div class="col-6 text-end"><?= htmlspecialchars($student['class'] ?? '-') ?></div>
            <div class="col-6 text-start"><strong>Status:</strong></div>
            <div class="col-6 text-end"><?= htmlspecialchars($student['status'] ?? '-') ?></div>
        </div>
        <hr>
        <h5 class="mb-3">Quick Access</h5>
        <div class="d-grid gap-2 mb-2">
            <a href="../registration/full_register.php" class="btn btn-warning">Complete Full Registration</a>
            <a href="view_results.php" class="btn btn-success">View Results</a>
            <a href="generate_result_pdf.php" class="btn btn-dark">Download Result (PDF)</a>
            <a href="view_assignments.php" class="btn btn-info">View Assignments</a>
            <a href="view_attendance.php" class="btn btn-primary">View Attendance</a>
        </div>
        <hr>
        <a href="../../logout.php" class="btn btn-danger w-100">Logout</a>
    </div>
</div>
</body>
</html>