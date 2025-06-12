<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 🔐 Ensure teacher access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// 👉 Fetch teacher info
$stmt = $conn->prepare("SELECT full_name, profile_photo FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$teacher_name = $teacher_data['full_name'] ?? 'Teacher';
$photo_file = $teacher_data['profile_photo'] ?? '';
$teacher_photo = (!empty($photo_file) && file_exists("../../uploads/$photo_file"))
    ? "../../uploads/" . htmlspecialchars($photo_file)
    : 'https://ui-avatars.com/api/?name=' . urlencode($teacher_name) . '&background=2563eb&color=fff';

// 👉 Get teacher's class
$stmt = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($class_assigned);
$stmt->fetch();
$stmt->close();
$class_assigned = trim($class_assigned ?? '');

// 👉 Get students in class
$students = [];
if ($class_assigned) {
    $stmt = $conn->prepare("SELECT student_id, full_name FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
    $stmt->bind_param("s", $class_assigned);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 👉 Get assignments
$assignments = [];
$stmt = $conn->prepare("SELECT id, title, subject, due_date, date_posted FROM assignments WHERE teacher_id = ? AND class_assigned = ? ORDER BY date_posted DESC");
$stmt->bind_param("is", $teacher_id, $class_assigned);
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 👉 Generate 40-minute periods
function generate_periods($start = "07:30", $end = "15:00") {
    $slots = [];
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    while ($start_time < $end_time) {
        $next = strtotime("+40 minutes", $start_time);
        $slots[] = [date("H:i", $start_time), date("H:i", $next)];
        $start_time = $next;
    }
    return $slots;
}
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = generate_periods();
$subjects = ['Math', 'English', 'Science', 'Social Studies', 'Civic', 'ICT', 'PHE', 'Art']; // Example subjects
$_POST['attendance_status'] = [
  'STU001' => 'present',
  'STU002' => 'absent',
  
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8fafc; }
        .sidebar {
            background: linear-gradient(135deg, #2563eb, #1e293b);
            color: white;
            min-height: 100vh;
        }
        .profile-img {
            width: 60px; height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 sidebar py-4">
            <div class="text-center">
                <img src="<?= $teacher_photo ?>" class="profile-img mb-2" alt="Teacher">
                <h5><?= htmlspecialchars($teacher_name) ?></h5>
                <small>Class: <?= htmlspecialchars($class_assigned ?: 'Unassigned') ?></small>
                <hr>
                <a href="profile.php" class="btn btn-outline-light btn-sm mb-2">Edit Profile</a>
            </div>
            <ul class="nav flex-column px-3">
                <li><a class="nav-link text-white" href="#">Dashboard</a></li>
                <li><a class="nav-link text-white" href="manage_record_ca_exam_results.php">Submit Results</a></li>
                <li><a class="nav-link text-white" href="post_assignment.php">Post Assignment</a></li>
                <li><a class="nav-link text-white" href="view_assignment.php">View Assignments</a></li>
                <li><a class="nav-link text-white" href="download_results.php">Download Results</a></li>
                <li><a class="nav-link text-white" href="view_submitted_results.php">View Submitted Results</a></li>
                <li class="mt-3"><a class="btn btn-danger w-100" href="../../logout.php">Logout</a></li>
            </ul>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 p-4">
            <h3 class="text-primary mb-4">Welcome, <?= htmlspecialchars($teacher_name) ?></h3>

            <!-- 🧍 Student Attendance -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Mark Attendance</div>
                <div class="card-body">
                    <form method="POST" action="mark_attendance.php">
    <table class="table table-bordered">
        <thead>
            <tr><th>#</th><th>Student Name</th><th>Status</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($students as $student): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                    <td>
                        <select name="attendance_status[<?= $student['student_id'] ?>]" class="form-select">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="remarks[<?= $student['student_id'] ?>]" class="form-control" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-success">Submit Attendance</button>
</form>

                </div>
            </div>

            <!-- 📅 Weekly Timetable -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">Weekly Timetable (40-min per Period)</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered text-center">
                        <thead><tr><th>Time</th><?php foreach ($days as $day) echo "<th>$day</th>"; ?></tr></thead>
                        <tbody>
                        <?php foreach ($periods as [$start, $end]): ?>
                            <tr>
                                <td><?= "$start - $end" ?></td>
                                <?php foreach ($days as $day): ?>
                                    <td><?= $subjects[array_rand($subjects)] ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 📚 Assignments -->
            <div class="card">
                <div class="card-header bg-primary text-white">Posted Assignments</div>
                <div class="card-body table-responsive">
                    <?php if ($assignments): ?>
                        <table class="table table-bordered">
                            <thead><tr><th>#</th><th>Title</th><th>Subject</th><th>Due</th><th>Posted</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php $n = 1; foreach ($assignments as $a): ?>
                                    <tr>
                                        <td><?= $n++ ?></td>
                                        <td><?= htmlspecialchars($a['title']) ?></td>
                                        <td><?= htmlspecialchars($a['subject']) ?></td>
                                        <td><?= htmlspecialchars($a['due_date']) ?></td>
                                        <td><?= htmlspecialchars($a['date_posted']) ?></td>
                                        <td><a href="view_submissions.php?assignment_id=<?= $a['id'] ?>" class="btn btn-sm btn-primary">Submissions</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No assignments yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
