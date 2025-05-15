<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

// Get teacher's data
$teacher_id = $_SESSION['user_id'];
$stmt_teacher = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$result_teacher = $stmt_teacher->get_result();
$teacher_data = $result_teacher->fetch_assoc();

// Get class_assigned for this teacher
$class_assigned = $teacher_data['class_assigned'] ?? '';

// Teacher photo logic
$teacher_photo = !empty($teacher_data['profile_photo'])
    ? '../uploads/' . htmlspecialchars($teacher_data['profile_photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($teacher_data['full_name'] ?? $teacher_data['name'] ?? 'Teacher') . '&background=2563eb&color=fff';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #2563eb 0%, #1e293b 100%);
            color: #fff;
            padding: 0;
            transition: left 0.3s;
        }
        .sidebar .nav-link, .sidebar .btn {
            color: #fff;
            font-weight: 500;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover, .sidebar .btn:hover {
            background: #1e40af;
            color: #fff;
        }
        .profile-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
        }
        .sidebar-header {
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .logout-btn {
            margin-top: 2rem;
            width: 100%;
        }
        .sidebar-toggler {
            display: none;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                width: 240px;
                z-index: 1050;
                height: 100%;
                border-radius: 0 1rem 1rem 0;
                box-shadow: 2px 0 12px rgba(0,0,0,0.08);
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: inline-block;
                position: absolute;
                left: 10px;
                top: 10px;
                z-index: 1100;
                background: #2563eb;
                color: #fff;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 1.5rem;
                line-height: 1;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(30, 41, 59, 0.4);
                z-index: 1049;
            }
            .overlay.show {
                display: block;
            }
        }
        @media (min-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<button class="sidebar-toggler d-lg-none" id="sidebarToggle">&#9776;</button>
<div class="overlay" id="sidebarOverlay"></div>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <nav class="col-lg-3 col-md-4 sidebar px-0" id="sidebarNav">
            <div class="sidebar-header">
                <img src="<?= $teacher_photo ?>" alt="Profile" class="profile-img mb-2">
                <h5 class="mb-0"><?= htmlspecialchars($teacher_data['full_name'] ?? $teacher_data['name'] ?? 'Teacher') ?></h5>
                <small class="text-light">Teacher</small>
                <div class="mb-2"><span class="badge bg-light text-dark">Class: <?= htmlspecialchars($class_assigned ?: 'Not Assigned') ?></span></div>
                <a href="profile.php" class="btn btn-outline-secondary mb-3">My Profile - Edit</a>
            </div>
            <ul class="nav flex-column mt-4 px-2">
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="#">Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="download_results.php">Download Results</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="record_assessment.php">Record Assessment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="record_exam.php">Record Exam</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="assign_homework.php">Assign Homework</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="post_assignment.php">Post Assignment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="view_assignment.php">View Assignment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="record_results.php">Submit Student Results</a>
                </li>
            </ul>
            <a href="../../logout.php" class="btn btn-danger logout-btn mt-auto mb-3">Logout</a>
        </nav>
        <!-- Main Content -->
        <main class="col-lg-9 col-md-8 ms-sm-auto px-4 py-4 main-content" id="mainContent">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                <h3 class="text-primary mb-3 mb-md-0">Welcome, <?= htmlspecialchars($teacher_data['full_name'] ?? $teacher_data['name'] ?? 'Teacher') ?></h3>
                <span class="badge bg-primary fs-6">Class: <?= htmlspecialchars($class_assigned ?: 'Not Assigned') ?></span>
            </div>
            <div class="row g-4">
                <!-- Quick Links -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex flex-column gap-2">
                            <a href="record_results.php" class="btn btn-primary w-100">Submit Student Results</a>
                            <a href="record_assessment.php" class="btn btn-info w-100">Record Assessment</a>
                            <a href="post_assignment.php" class="btn btn-secondary w-100">Post Assignment</a>
                            <a href="view_assignment.php" class="btn btn-secondary w-100">View Assignment</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex flex-column gap-2">
                            <a href="assign_homework.php" class="btn btn-warning w-100">Assign Homework</a>
                            <a href="download_results.php" class="btn btn-success w-100">Download Results</a>
                        </div>
                    </div>
                </div>
                
<!-- display student assignment -->
<div class="col-12 mt-4">
    <h4 class="text-center">Student Assignments</h4>
    <div class="alert alert-info" role="alert">
        Here are the assignments you have posted for your students in <strong><?= htmlspecialchars($class_assigned ?: 'Not Assigned') ?></strong>.
    </div>
    <h5 class="mt-4">Submitted Assignments</h5>
    <?php
    // Fetch assignments/submissions for this teacher and class
    $stmt = $conn->prepare("
        SELECT a.*, s.full_name
        FROM assignments a
        LEFT JOIN students s ON a.student_id = s.id
        WHERE a.teacher_id = ? AND a.class = ?
        ORDER BY a.date_posted DESC
    ");
    $stmt->bind_param("is", $teacher_id, $class_assigned);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Assignment Title</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Submission File</th>
                    <th>Text Assignment</th>
                    <th>Date Posted</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['full_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['student_id'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($row['submission_file'])): ?>
                            <a href="<?= htmlspecialchars($row['submission_file']) ?>" target="_blank">📄 View</a>
                        <?php else: ?>
                            <span class="text-danger">Not submitted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($row['submission_text'])): ?>
                            <?= nl2br(htmlspecialchars($row['submission_text'])) ?>
                        <?php else: ?>
                            <span class="text-secondary">No Text</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['date_posted']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No submissions yet.</p>
    <?php endif; ?>
</div>
            </div>
        </main>
    </div>
</div>

<script>
    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebarNav');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    });
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
</script>
</body>
</html>