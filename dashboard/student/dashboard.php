<?php
// Enable strict error reporting for development to catch potential issues early.
declare(strict_types=1);

// Start the session to access session variables.
session_start();

// Include the database configuration file.
require_once "../../config.php";

// --- Authentication Check ---
if (!isset($_SESSION['student_unique_id']) || empty($_SESSION['student_unique_id'])) {
    // If the student's unique ID is not set in the session, redirect to login.
    header("Location: student_login.php");
    exit;
}

// Get the student's unique ID from the session.
$studentUniqueId = $_SESSION['student_unique_id'];

// Initialize student data variable.
$studentData = null;
$teacherData = null;

try {
    // Prepare a query to fetch student data based on unique ID.
    $stmt = $conn->prepare("SELECT full_name, class_assigned, passport_photo FROM students WHERE unique_id = ?");

    if ($stmt) {
        $stmt->bind_param("s", $studentUniqueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $studentData = $result->fetch_assoc();
        $stmt->close();
    } else {
        error_log("Dashboard Error: Prepare query failed: " . $conn->error);
        echo "<div class='alert alert-danger mt-3'>Error fetching student data. Please contact support.</div>";
    }

    // --- Fetch Teacher Data ---
    if ($studentData && !empty($studentData['class_assigned'])) {
        $teacherStmt = $conn->prepare("SELECT full_name, profile_photo FROM users WHERE role = 'teacher' AND class_assigned = ? LIMIT 1");
        if ($teacherStmt) {
            $teacherStmt->bind_param("s", $studentData['class_assigned']);
            $teacherStmt->execute();
            $teacherResult = $teacherStmt->get_result();
            $teacherData = $teacherResult->fetch_assoc();
            $teacherStmt->close();
        } else {
            error_log("Dashboard Error: Prepare query for teacher failed: " . $conn->error);
            echo "<div class='alert alert-danger mt-3'>Error fetching teacher data. Please contact support.</div>";
        }
    }

} catch (Exception $e) {
    error_log("Dashboard Error: Database query error: " . $e->getMessage());
    echo "<div class='alert alert-danger mt-3'>Database error occurred. Please try again later.</div>";
}

// --- Handle missing student data ---
if (!$studentData) {
    error_log("Dashboard Error: No student data found for Unique ID: " . htmlspecialchars($studentUniqueId));
    echo "<div class='alert alert-warning mt-3'>Student data not found. Please contact admin.</div>";
    // You might want to redirect to a logout page or display a specific error here.
}

// --- Prepare data for display ---
$fullName = htmlspecialchars($studentData['full_name'] ?? 'Guest Student');
$classAssigned = htmlspecialchars($studentData['class_assigned'] ?? 'N/A');
$passportPhoto = htmlspecialchars($studentData['passport_photo'] ?? '');
$studentAvatar = !empty($passportPhoto)
    ? "../../uploads/passports/" . $passportPhoto
    : "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=2563eb&color=fff";

$teacherName = htmlspecialchars($teacherData['full_name'] ?? 'No Teacher Assigned');
$teacherProfilePhoto = htmlspecialchars($teacherData['profile_photo'] ?? '');
$teacherAvatar = !empty($teacherProfilePhoto)
    ? "../../uploads/" . $teacherProfilePhoto
    : "https://ui-avatars.com/api/?name=" . urlencode($teacherName) . "&background=2563eb&color=fff";

?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .dashboard-container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 1rem; box-shadow: 0 8px 20px rgba(44,62,80,0.08); padding: 2rem; }
        .student-profile { display: flex; align-items: center; margin-bottom: 20px; }
        .student-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #2563eb; margin-right: 20px; }
        .teacher-info { display: flex; align-items: center; margin-bottom: 20px; }
        .teacher-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff; margin-right: 20px; }
        .dashboard-links a { margin-right: 10px; margin-bottom: 10px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">Student Dashboard</h2>
                <a href="../../logout.php" class="btn btn-danger">Logout</a>
            </div>

            <div class="student-profile">
                <img src="<?= $studentAvatar ?>" alt="Student Photo" class="student-avatar">
                <div>
                    <h3><?= $fullName ?></h3>
                    <p class="text-muted">Class: <?= $classAssigned ?></p>
                    <p class="text-muted">Unique ID: <?= htmlspecialchars($studentUniqueId) ?></p>
                </div>
            </div>

            <hr class="mb-4">

            <div class="teacher-info">
                <img src="<?= $teacherAvatar ?>" alt="Teacher Photo" class="teacher-avatar">
                <div>
                    <h5>Your Class Teacher</h5>
                    <h4><?= $teacherName ?></h4>
                </div>
            </div>

            <hr class="mb-4">

            <div class="dashboard-links">
                <h4>Quick Access</h4>
                <a href="view_results.php" class="btn btn-primary"><i class="bi bi-file-earmark-text me-2"></i> View Results</a>
                <a href="view_assignments.php" class="btn btn-info"><i class="bi bi-book me-2"></i> View Assignments</a>
                <!-- <a href="submit_assignment.php" class="btn btn-warning"><i class="bi bi-upload me-2"></i> Submit Assignment</a> -->
                <a href="registration/full_registration.php" class="btn btn-success"><i class="bi bi-person-gear me-2"></i> Update Profile</a>
                <a href="change_password.php" class="btn btn-warning"><i class="bi bi-key me-2"></i> Change Password<small> || You must change your password upon first login</small></a>
                <a href="generate_id_card.php" class="btn btn-secondary"><i class="bi bi-person-badge me-2"></i> Generate ID Card</a>
            </div>

            <hr class="mt-4">

            <p class="text-muted text-center">Logged in as: <?= $fullName ?></p>
            <p class="text-muted text-center"><a href="student_login.php" class="btn btn-secondary btn-sm">Back to Student Login</a> </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>