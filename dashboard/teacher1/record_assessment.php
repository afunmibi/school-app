<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$message = "";
$teacher_user_id = $_SESSION['user_id'];
$teacher_id_for_db = $teacher_user_id; // This is users.id, will be used for assessments.teacher_id
$teacher_class_assigned = '';
$students_in_class = [];

// Fetch teacher's assigned class from the 'teachers' table (consistent with other teacher scripts)
// The $teacher_id_for_db for the assessments table is already $teacher_user_id (users.id)
$stmt_teacher_class_fetch = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
if ($stmt_teacher_class_fetch) {
    $stmt_teacher_class_fetch->bind_param("i", $teacher_user_id);
    $stmt_teacher_class_fetch->execute();
    $result_teacher_class = $stmt_teacher_class_fetch->get_result();
    if ($teacher_class_info = $result_teacher_class->fetch_assoc()) {
        $teacher_class_assigned = trim($teacher_class_info['class_assigned']);
    }
    $stmt_teacher_class_fetch->close();
} else {
    error_log("Failed to prepare statement to fetch teacher's class: " . $conn->error);
    $message = "<div class='alert alert-danger'>Error fetching teacher class information. Please contact support.</div>";
}

if (empty($message) && !empty($teacher_class_assigned)) {
    // Fetch students in the teacher's assigned class (using registration_id as the value for the form)
    $stmt_students = $conn->prepare("SELECT id, registration_id, full_name FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
    if ($stmt_students) {
        $stmt_students->bind_param("s", $teacher_class_assigned);
        $stmt_students->execute();
        $result_students_fetch = $stmt_students->get_result();
        while ($student_row = $result_students_fetch->fetch_assoc()) {
            $students_in_class[] = $student_row;
        }
        $stmt_students->close();
        if (empty($students_in_class) && empty($message)) { 
            $message = "<div class='alert alert-info'>No students found in your assigned class: " . htmlspecialchars($teacher_class_assigned) . ".</div>";
        }
    } else {
        error_log("Failed to prepare statement to fetch students: " . $conn->error);
        $message = "<div class='alert alert-danger'>Error fetching students. Please contact support.</div>";
    }
} elseif (empty($message) && empty($teacher_class_assigned)) {
    $message = "<div class='alert alert-warning'>You are not currently assigned to a class. Please contact an administrator to get assigned.</div>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $student_db_id = intval($_POST['student_id']); // This will now be students.id (PK, integer)
    $subject_collected = trim($_POST['subject']); // Collected, but no 'subject' column in the provided 'assessments' schema
    $score = intval($_POST['score']);
    $term_collected = trim($_POST['term']); // Collected, but no 'term' column
    $session_year_collected = trim($_POST['session']); // Collected, but no 'session' column
    // $teacher_class_assigned is available, but no 'class_assigned' column for the assessment context

    // Data for the 'assessments' table based on the provided schema:
    // id, student_id, assessment_score, teacher_id, date_assessed, 
    // submission_date, submission_file, submission_text, unique_id

    $date_assessed = date('Y-m-d H:i:s');
    $submission_date_val = $date_assessed; // Or NULL if appropriate and column allows
    $submission_file_val = NULL;        // Assuming NULL for general assessment
    $submission_text_val = '';        // Provide an empty string if column cannot be NULL
    $unique_assessment_id = uniqid('asm_', true); // Generate a unique ID

    $stmt_insert = $conn->prepare("INSERT INTO assessments (student_id, assessment_score,subject, teacher_id, date_assessed, submission_date,  submission_text, unique_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind parameters: s=string, i=integer
    // student_id (INT), assessment_score (INT), subject (VARCHAR), teacher_id (INT), date_assessed (DATETIME string)
    // submission_date (DATETIME string), submission_text (TEXT), unique_id (VARCHAR)
    $stmt_insert->bind_param("iisiisss", 
        $student_db_id, 
        $score, 
        $subject_collected,
        $teacher_id_for_db,
        $date_assessed,
        $submission_date_val,
        
        $submission_text_val,
        $unique_assessment_id);

    if ($stmt_insert->execute()) {
        $message = "<div class='alert alert-success'>✅ Assessment score submitted successfully for " . htmlspecialchars($subject_collected) . ".</div>";
    } else {
        $message = "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($stmt_insert->error) . "</div>";
    }
    $stmt_insert->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Assessment Score</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        .container {
            margin-top: 50px;
            max-width: 700px; 
        }
        .alert {
            margin-bottom: 20px;
        }
        .card-header h4 {
            text-align: center;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h4>Record Student Assessment Score</h4>
            </div>
            <div class="card-body">
                <?= $message ?>
                <div class="alert alert-secondary" role="alert">
                    Please fill in the details below to record student assessment scores for class: <strong><?= htmlspecialchars($teacher_class_assigned ?: 'Not Assigned') ?></strong>.
                </div>
                <form method="POST" action="record_assessment.php" class="row g-3">
                    <div class="col-md-12">
                        <label for="student_id" class="form-label">Student</label>
                        <select name="student_id" id="student_id" class="form-select" <?= empty($students_in_class) ? 'disabled' : 'required' ?>>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students_in_class as $student): ?>
                                <option value="<?= htmlspecialchars($student['id']) ?>">
                                    <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['registration_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" placeholder="Subject" required>
                    </div>
                    <div class="col-md-6">
                        <label for="score" class="form-label">Assessment Score</label>
                        <input type="number" name="score" id="score" class="form-control" placeholder="Score (0-100)" min="0" max="100" required>
                    </div>
                    <div class="col-md-6">
                        <label for="term" class="form-label">Term</label>
                        <input type="text" name="term" id="term" class="form-control" placeholder="e.g. 1st Term" required>
                    </div>
                    <div class="col-md-6">
                        <label for="session" class="form-label">Session</label>
                        <input type="text" name="session" id="session" class="form-control" placeholder="e.g. 2023/2024" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" <?= empty($students_in_class) ? 'disabled' : '' ?>>Submit Score</button>
                        <a href="./dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
