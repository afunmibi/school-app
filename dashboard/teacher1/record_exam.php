<?php
session_start();
ini_set('display_errors', 1); // Recommended for development
error_reporting(E_ALL);     // Recommended for development
include "../../config.php";

// Ensure teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php"); // Corrected path to main login
    exit;
}

$message = "";
$teacher_user_id = $_SESSION['user_id'];
$teacher_id_for_db = $teacher_user_id; // This is users.id, used for inserting into exam_scores.teacher_id
$teacher_class_assigned = '';
$students_in_class = [];

// Fetch teacher's full_name and email from users table
$user_full_name = '';
$user_email = '';
$stmt_user_data = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
if ($stmt_user_data) {
    $stmt_user_data->bind_param("i", $teacher_user_id);
    $stmt_user_data->execute();
    $result_user_data = $stmt_user_data->get_result();
    if ($user_data_row = $result_user_data->fetch_assoc()) {
        $user_full_name = $user_data_row['full_name'];
        $user_email = $user_data_row['email'];
    }
    $stmt_user_data->close();
} else {
    error_log("Failed to prepare statement to fetch user data: " . $conn->error);
    $message = "<div class='alert alert-danger'>Error fetching teacher's basic information.</div>";
}

// Fetch teacher's assigned class from the 'teachers' table (teacher_id in 'teachers' table is FK to users.id)
$stmt_teacher_class_fetch = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
if ($stmt_teacher_class_fetch) {
    $stmt_teacher_class_fetch->bind_param("i", $teacher_user_id);
    $stmt_teacher_class_fetch->execute();
    $result_teacher_class_fetch = $stmt_teacher_class_fetch->get_result();
    if ($teacher_class_info = $result_teacher_class_fetch->fetch_assoc()) {
        $teacher_class_assigned = $teacher_class_info['class_assigned'];
    }
    $stmt_teacher_class_fetch->close();
} else {
    error_log("Failed to prepare statement to fetch teacher's class from teachers table: " . $conn->error);
    if (empty($message)) $message = "<div class='alert alert-danger'>Error fetching teacher class information.</div>";
}

// Synchronize the 'teachers' table with data from the 'users' table.
// This step ensures that:
// 1. A record for the current teacher (based on users.id) exists in the 'teachers' table.
// 2. The 'full_name' and 'email' in the 'teachers' table are up-to-date with the values from the 'users' table.
// 3. The 'class_assigned' field in the 'teachers' table is preserved if the teacher record already exists,
//    or set (typically to an empty string if no class was found) if a new teacher record is being inserted.
if (empty($message) && !empty($user_full_name) && !empty($user_email)) { // Proceed only if user data is available and no prior critical errors.
    $stmt_sync_teachers = $conn->prepare("
        INSERT INTO teachers (teacher_id, full_name, email, class_assigned) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), email = VALUES(email), class_assigned = VALUES(class_assigned)
    ");
    // IMPORTANT: This assumes `teacher_id` is a UNIQUE KEY or PRIMARY KEY in the `teachers` table
    // for `ON DUPLICATE KEY UPDATE` to work as intended (i.e., update the existing teacher's record).
    // If `teacher_id` is not unique, this could lead to unexpected behavior or errors.
    // The `$teacher_class_assigned` variable holds the class fetched earlier (or '' if not found/not set).
    // - For an existing teacher, `class_assigned = VALUES(class_assigned)` effectively re-sets it to its current value.
    // - For a new teacher entry, `class_assigned` will be set to the value of `$teacher_class_assigned`.
    if ($stmt_sync_teachers) {
        $stmt_sync_teachers->bind_param("isss", $teacher_user_id, $user_full_name, $user_email, $teacher_class_assigned);
        if (!$stmt_sync_teachers->execute()) {
            error_log("Failed to execute teachers table synchronization: " . $stmt_sync_teachers->error);
            // Optionally, set a user-facing message if this is critical
            // if (empty($message)) $message = "<div class='alert alert-warning'>A system error occurred while updating teacher records. Please try again later.</div>";
        }
        $stmt_sync_teachers->close();
    } else {
        error_log("Failed to prepare statement for synchronizing teachers table: " . $conn->error);
        // if (empty($message)) $message = "<div class='alert alert-danger'>Critical error: Could not prepare teacher record synchronization.</div>";
    }
}

// This block is an adaptation of the POST handling from record_assessment.php,
// modified for the record_exam.php form and the exam_scores table.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inputs from the record_exam.php form:
    // $_POST['student_id'], $_POST['full_name'], $_POST['student_class'], $_POST['subject'], $_POST['exam_score']

    // Sanitize and retrieve inputs
    $student_db_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $subject_from_form = isset($_POST['subject']) ? trim($_POST['subject']) : ''; // For context/validation, not stored in exam_scores
    
    $exam_score_val = null; // Initialize
    if (isset($_POST['exam_score']) && $_POST['exam_score'] !== '') { // Check if set and not an empty string
        $exam_score_val = intval($_POST['exam_score']);
    }

    // Other form fields for validation (auto-filled by JS, but good to check their presence)
    $full_name_collected = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $class_collected = isset($_POST['student_class']) ? trim($_POST['student_class']) : '';

    // Data for the 'exam_scores' table
    // Schema: id, student_id, score, teacher_id, date_recorded
    $date_recorded = date('Y-m-d'); // exam_scores uses DATE format

    // Validation: Check if essential fields are provided and valid
    // $teacher_id_for_db is available from session/earlier script part ($teacher_user_id)
    if (empty($student_db_id) || 
        empty($full_name_collected) || // Check auto-filled field
        empty($class_collected) ||     // Check auto-filled field
        empty($subject_from_form) ||   // Check subject field from form
        $exam_score_val === null || $exam_score_val < 0 || $exam_score_val > 100 // Check score presence and range
    ) {
        $message = "<div class='alert alert-danger'>❌ Please select a student, ensure name/class are auto-filled, fill in the subject, and enter a valid exam score (0-100).</div>";
    } else {
        // Prepare INSERT statement for the 'exam_scores' table
        $stmt_insert = $conn->prepare("INSERT INTO exam_scores (student_id, score, teacher_id, date_recorded) VALUES (?, ?, ?, ?)");
        
        if ($stmt_insert) {
            // Bind parameters: student_id (INT), score (INT), teacher_id (INT), date_recorded (DATE string)
            $stmt_insert->bind_param("iiis", 
                $student_db_id, 
                $exam_score_val, 
                $teacher_id_for_db, // This is $teacher_user_id from session
                $date_recorded
            );
            
            if ($stmt_insert->execute()) {
                $student_display_name = !empty($full_name_collected) ? htmlspecialchars($full_name_collected) : "the selected student";
                $subject_display_name = !empty($subject_from_form) ? " for " . htmlspecialchars($subject_from_form) : "";
                $message = "<div class='alert alert-success'>✅ Exam score submitted successfully for " . $student_display_name . $subject_display_name . ".</div>";
            } else {
                // Check for duplicate entry if a unique constraint might be violated (e.g., student_id, date_recorded if that was unique)
                if ($conn->errno == 1062) { // MySQL error number for duplicate entry
                     $message = "<div class='alert alert-danger'>❌ Error: A similar exam record for this student might already exist. Please check the details.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>❌ Error submitting exam score: " . htmlspecialchars($stmt_insert->error) . "</div>";
                }
            }
            $stmt_insert->close();
        } else {
            $message = "<div class='alert alert-danger'>❌ Error preparing statement for exam score submission: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
}

// Fetch students for dropdown, filtered by teacher's class
if (empty($message) && !empty($teacher_class_assigned)) {
    // Fetch students in the teacher's assigned class
    // We need students.id (PK) for the value and students.registration_id for display if needed
    $stmt_students_fetch = $conn->prepare("SELECT id, registration_id, full_name, class_assigned FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
    if ($stmt_students_fetch) {
        $stmt_students_fetch->bind_param("s", $teacher_class_assigned);
        $stmt_students_fetch->execute();
        $result_students_data = $stmt_students_fetch->get_result();
        while ($student_row_data = $result_students_data->fetch_assoc()) {
            $students_in_class[] = $student_row_data;
        }
        $stmt_students_fetch->close();
        if (empty($students_in_class) && empty($message)) {
             $message = "<div class='alert alert-info'>No students found in your assigned class: " . htmlspecialchars($teacher_class_assigned) . ".</div>";
             // Disable form elements if no students? The HTML select already handles this.
        }
    } else {
        error_log("Failed to prepare statement to fetch students: " . $conn->error);
        if (empty($message)) $message = "<div class='alert alert-danger'>Error fetching students. Please contact support.</div>";
    }
} elseif (empty($message) && empty($teacher_class_assigned)) { // If no class assigned from teachers table
    if (empty($message)) $message = "<div class='alert alert-warning'>You are not currently assigned to a class. Please check your profile or contact an administrator.</div>";
}

// Synchronize 'teacher_classes' table
// The purpose of 'teacher_classes' table and its exact schema (especially unique keys) is crucial here.
// This current sync logic assumes it might store a teacher's name against their assigned class.
// If $teacher_class_assigned is empty, this block is skipped.
if (empty($message) && $user_full_name && !empty($teacher_class_assigned)) {
    $stmt_sync_teacher_classes = $conn->prepare("
        INSERT INTO teacher_classes (teacher_id, full_name, class_assigned) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE full_name = VALUES(full_name) 
    "); 
    // IMPORTANT: This assumes a UNIQUE KEY on (teacher_id, class_assigned) or just teacher_id if a teacher can only be in teacher_classes once.
    // If the intent is to update the full_name for an existing teacher-class pair.
    if ($stmt_sync_teacher_classes) {
        $stmt_sync_teacher_classes->bind_param("iss", $teacher_user_id, $user_full_name, $teacher_class_assigned);
        $stmt_sync_teacher_classes->execute();
        $stmt_sync_teacher_classes->close();
    } else {
        error_log("Failed to prepare statement for synchronizing teacher_classes table: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Exam Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; } /* Adjust overall container width if needed */
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Record Exam Result</h4>
        </div>
        <div class="card-body">
            <?= $message ?>
            <form action="record_exam.php" method="POST" class="row g-3">
                <div class="col-12">
                    <label for="student_id" class="form-label">Select Student (Class: <?= htmlspecialchars($teacher_class_assigned ?: 'Not Assigned') ?>)</label>
                    <select name="student_id" id="student_id" class="form-select" <?= empty($students_in_class) ? 'disabled' : 'required' ?>>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students_in_class as $student): ?>
                            <option value="<?= $student['id'] ?>" 
                                    data-name="<?= htmlspecialchars($student['full_name']) ?>" 
                                    data-class="<?= htmlspecialchars($student['class_assigned']) ?>">
                                <?= htmlspecialchars($student['full_name']) ?> (ID: <?= htmlspecialchars($student['registration_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Student's name will appear here" readonly required>
                </div>

                <div class="col-md-6">
                    <label for="student_class" class="form-label">Class</label>
                    <input type="text" id="student_class" name="student_class" class="form-control" value="<?= htmlspecialchars($teacher_class_assigned ?: 'N/A') ?>" placeholder="Student's class will appear here" readonly required>
                </div>

                <div class="col-md-6">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="Enter Subject" required>
                </div>

                <div class="col-md-6">
                    <label for="exam_score" class="form-label">Exam Score</label>
                    <input type="number" name="exam_score" id="exam_score" class="form-control" placeholder="Enter score (0-100)" min="0" max="100" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success" <?= (empty($students_in_class) || empty($teacher_class_assigned)) ? 'disabled' : '' ?>>Submit Result</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-muted" style="display: none;"> <!-- Hidden as status is not in exam_scores -->
            <small>Note: All results are pending approval by the admin.</small>
    </div>
    </div>
        <div class="text-center mt-3">
            <a href="./dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
</div>

<script>
    const studentSelect = document.getElementById('student_id');
    const nameField = document.getElementById('full_name');
    const classField = document.getElementById('student_class');

    studentSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        if (selected.value) { // If a student is selected (not the "-- Choose Student --" option)
            nameField.value = selected.getAttribute('data-name') || '';
            classField.value = selected.getAttribute('data-class') || '';
        } else { // Reset if "-- Choose Student --" is selected
            nameField.value = '';
            classField.value = '<?= htmlspecialchars($teacher_class_assigned ?: 'N/A') ?>'; // Reset to teacher's class or N/A
        }
    });
</script>
</body>
</html>