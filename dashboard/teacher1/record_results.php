<?php
session_start();
include "../../config.php";

// Ensure teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php"); // Corrected path to main login
    exit;
}

$teacher_user_id = $_SESSION['user_id']; // This is users.id
$class_assigned = '';
$students = [];
$message = ""; // Initialize message

// Fetch teacher's assigned class from the 'teachers' table (consistent with dashboard and record_exam)
$stmt_teacher_class_fetch = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
if ($stmt_teacher_class_fetch) {
    $stmt_teacher_class_fetch->bind_param("i", $teacher_user_id);
    $stmt_teacher_class_fetch->execute();
    $result_teacher_class = $stmt_teacher_class_fetch->get_result();
    if ($teacher_class_info = $result_teacher_class->fetch_assoc()) {
        $class_assigned = trim($teacher_class_info['class_assigned']);
    }
    $stmt_teacher_class_fetch->close();
} else {
    error_log("Failed to prepare statement to fetch teacher's class: " . $conn->error);
    $message = "<div class='alert alert-danger'>Error fetching teacher class information. Please contact support.</div>";
}

if (empty($message)) { // Proceed only if no error so far
    if (!empty($class_assigned)) {
        // Fetch students in the assigned class
        // Fetch students.id as well for better data handling
        $stmt_students = $conn->prepare("SELECT id, registration_id, full_name FROM students WHERE class_assigned = ? ORDER BY full_name ASC");
        if ($stmt_students) {
            $stmt_students->bind_param("s", $class_assigned);
            $stmt_students->execute();
            $result_students = $stmt_students->get_result();
            while ($student_row = $result_students->fetch_assoc()) {
                $students[] = $student_row;
            }
            $stmt_students->close();

            if (empty($students)) {
                $message = "<div class='alert alert-info'>No students found in your assigned class: " . htmlspecialchars($class_assigned) . ".</div>";
            }
        } else {
            error_log("Failed to prepare statement to fetch students: " . $conn->error);
            $message = "<div class='alert alert-danger'>Error fetching students. Please contact support.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>You are not currently assigned to a class. Please contact an administrator to get assigned.</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_db_id = intval($_POST['student_id']); // This is now students.id (integer)
    $subject = trim($_POST['subject']);
    $term = trim($_POST['term']);
    $session_val = trim($_POST['session']);
    $full_name = '';

    // Fetch full_name for the selected student_id
    $stmt_name = $conn->prepare("SELECT full_name FROM students WHERE id = ?");
    if ($stmt_name) {
        $stmt_name->bind_param("i", $student_db_id);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if ($student_data = $result_name->fetch_assoc()) {
            $full_name = $student_data['full_name'];
        }
        $stmt_name->close();
    }

    if (!$full_name) {
        $message = "<div class='alert alert-danger'>Invalid student selected.</div>";
    } else {
        // Fetch exam score from 'exam_results' table
        // Assuming 'exam_results' has student_id (INT FK to students.id) and subject
        // Term and session are not in exam_results, so we can't use them for lookup here.
        // This might fetch an exam score not specific to the entered term/session.
        $exam_score = 0;
        $stmt_exam = $conn->prepare("SELECT exam_score FROM exam_results WHERE student_id = ? AND subject = ? ORDER BY result_date DESC LIMIT 1"); // Get latest for the subject
        if ($stmt_exam) {
            $stmt_exam->bind_param("is", $student_db_id, $subject);
            $stmt_exam->execute();
            $stmt_exam->bind_result($exam_score_fetched);
            if ($stmt_exam->fetch()) {
                $exam_score = $exam_score_fetched ?: 0;
            }
            $stmt_exam->close();
        }

        // Fetch assessment score from 'assessments' table
        // Assuming 'assessments' has student_id (INT FK to students.id)
        // Subject, term, and session are not in assessments table.
        // This might fetch an assessment score not specific to the entered subject/term/session.
        $assessment_score = 0;
        $stmt_assess = $conn->prepare("SELECT assessment_score FROM assessments WHERE student_id = ? ORDER BY date_assessed DESC LIMIT 1"); // Get latest assessment
        if ($stmt_assess) {
            $stmt_assess->bind_param("i", $student_db_id);
            $stmt_assess->execute();
            $stmt_assess->bind_result($assessment_score_fetched);
            if ($stmt_assess->fetch()) {
                $assessment_score = $assessment_score_fetched ?: 0;
            }
            $stmt_assess->close();
        }

        // Calculate total
        $total_score = $exam_score + $assessment_score;

        // Insert into results table
        // Ensure results.student_id is an INT to store students.id
        $stmt_insert_results = $conn->prepare("INSERT INTO results (full_name, student_id, class_assigned, subject, exam_score, assessment_score, total_score, term, session, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if ($stmt_insert_results) {
            // full_name (s), student_id (i), class_assigned (s), subject (s), exam_score (i), assessment_score (i), total_score (i), term (s), session (s)
            $stmt_insert_results->bind_param("sisiiisss", $full_name, $student_db_id, $class_assigned, $subject, $exam_score, $assessment_score, $total_score, $term, $session_val);
            if ($stmt_insert_results->execute()) {
                $message = "<div class='alert alert-success'>Result submitted successfully and is pending approval.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error submitting result: " . htmlspecialchars($stmt_insert_results->error) . "</div>";
            }
            $stmt_insert_results->close();
        } else {
            $message = "<div class='alert alert-danger'>Error preparing statement for result submission: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Student Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Record Student Results</h4>
            </div>
            <div class="card-body">
                <?= $message ?>
                <div class="alert alert-info">Select a student from your class and enter the result details.</div>
                <form method="POST" action="record_results.php" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= htmlspecialchars($student['id']) ?>">
                                    <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['registration_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Term</label>
                        <input type="text" name="term" class="form-control" placeholder="Term (e.g. 1st Term)" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Session</label>
                        <input type="text" name="session" class="form-control" placeholder="Session (e.g. 2024/2025)" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Exam Score</label>
                        <input type="number" id="exam_score" class="form-control" placeholder="Auto" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assessment Score</label>
                        <input type="number" id="assessment_score" class="form-control" placeholder="Auto" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Score</label>
                        <input type="number" id="total_score" class="form-control" placeholder="Auto" readonly>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Submit Result</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>