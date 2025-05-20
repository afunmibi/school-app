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

// Get teacher's data from users table (for name and profile photo)
$teacher_id = $_SESSION['user_id'];
$stmt_teacher = $conn->prepare("SELECT full_name, profile_photo FROM users WHERE id = ?");
$stmt_teacher->bind_param("i", $teacher_id);
$stmt_teacher->execute();
$result_teacher = $stmt_teacher->get_result();
$teacher_data = $result_teacher->fetch_assoc();
$stmt_teacher->close();

// Get class_assigned for this teacher from the 'teachers' table
$class_assigned = '';
$stmt_teacher_class = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
if ($stmt_teacher_class) {
    $stmt_teacher_class->bind_param("i", $teacher_id);
    $stmt_teacher_class->execute();
    $stmt_teacher_class->bind_result($class_assigned);
    $stmt_teacher_class->fetch();
    $stmt_teacher_class->close();
}
$class_assigned = trim($class_assigned ?? '');

// Teacher photo logic
$teacher_photo = !empty($teacher_data['profile_photo'])
    ? '../uploads/' . htmlspecialchars($teacher_data['profile_photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($teacher_data['full_name'] ?? $teacher_data['name'] ?? 'Teacher') . '&background=2563eb&color=fff';

// Fetch students in the teacher's assigned class
$students_in_class = [];
if (!empty($class_assigned)) {
    $class_assigned_for_query = strtolower(trim($class_assigned));
    $stmt_students_in_class = $conn->prepare(
        "SELECT student_id, full_name FROM students WHERE LOWER(TRIM(class_assigned)) = ? ORDER BY full_name ASC"
    );
    if ($stmt_students_in_class) {
        $stmt_students_in_class->bind_param("s", $class_assigned_for_query);
        $stmt_students_in_class->execute();
        $result_students_in_class = $stmt_students_in_class->get_result();
        while ($student_row = $result_students_in_class->fetch_assoc()) {
            $students_in_class[] = $student_row;
        }
        $stmt_students_in_class->close();
    }
}

// Fetch assignments posted by this teacher for their class
$assignments = [];
if (!empty($class_assigned)) {
    $stmt = $conn->prepare("
        SELECT id, title, subject, due_date, date_posted
        FROM assignments
        WHERE teacher_id = ? AND class = ?
        ORDER BY date_posted DESC
    ");
    $stmt->bind_param("is", $teacher_id, $class_assigned);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}
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

                <!-- My Students List -->
                <div class="col-12 mt-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Students in My Class: <?= htmlspecialchars($class_assigned ?: 'Not Assigned') ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($class_assigned)): ?>
                                <?php if (!empty($students_in_class)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student Name</th>
                                                    <th>Student ID</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $student_count = 1; ?>
                                                <?php foreach ($students_in_class as $student): ?>
                                                    <tr>
                                                        <td><?= $student_count++ ?></td>
                                                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mb-0" role="alert">
                                        No students found in your assigned class (<?= htmlspecialchars($class_assigned) ?>).
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0" role="alert">
                                    You are not currently assigned to a class. Please contact an administrator to get assigned.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Posted Assignments -->
                <div class="col-12 mt-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">My Posted Assignments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($assignments)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Assignment Title</th>
                                                <th>Subject</th>
                                                <th>Due Date</th>
                                                <th>Date Posted</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $assignment_count = 1; ?>
                                            <?php foreach ($assignments as $row): ?>
                                                <tr>
                                                    <td><?= $assignment_count++ ?></td>
                                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                                    <td><?= htmlspecialchars($row['due_date']) ?></td>
                                                    <td><?= htmlspecialchars($row['date_posted']) ?></td>
                                                    <td class="text-center">
                                                        <a href="view_submissions.php?assignment_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">View Submissions</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0" role="alert">
                                    No assignments posted yet for your class.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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