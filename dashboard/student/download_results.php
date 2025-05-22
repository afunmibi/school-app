<?php
session_start();
include "../../config.php";
require '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_unique_id = $_SESSION['student_id'];
$student_data = null;
$results_for_pdf = [];

// Fetch student info
$stmt = $conn->prepare("SELECT registration_id, full_name, passport_photo, student_id, class_assigned FROM students WHERE unique_id = ?");
if (!$stmt) {
    die("Error preparing student query: " . $conn->error);
}
$stmt->bind_param("s", $student_unique_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    die("Error: Student details not found. Please contact administration.");
}

$student_registration_id = $student_data['registration_id'];
$full_name = $student_data['full_name'] ?? 'N/A';
$passport = $student_data['passport_photo'] ?? '';
$official_student_id = $student_data['student_id'] ?? 'N/A';
$class_assigned = $student_data['class_assigned'] ?? 'N/A';

// Prepare passport image (if exists)
$passport_src = '';
if (!empty($passport)) {
    $passport_path = '../../Uploads/passports/' . $passport;
    if (file_exists($passport_path)) {
        $mime = mime_content_type($passport_path);
        $passport_data = base64_encode(file_get_contents($passport_path));
        $passport_src = 'data:' . $mime . ';base64,' . $passport_data;
    }
}

// Get and validate filters from GET parameters
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';
$valid_terms = ['First Term', 'Second Term', 'Third Term'];
if (!empty($term) && !in_array($term, $valid_terms)) {
    $term = '';
}
if (!empty($session_val) && !preg_match('/^\d{4}\/\d{4}$/', $session_val)) {
    $session_val = '';
}

// Fetch approved results from final_exam_results
if ($student_registration_id) {
    $query = "SELECT subject, term, session, assessments, exam_score, final_score, result_date 
              FROM final_exam_results 
              WHERE student_id = ? AND status = 'approved'";
    $params = [$student_registration_id];
    $types = "s";

    if (!empty($term)) {
        $query .= " AND term = ?";
        $params[] = $term;
        $types .= "s";
    }
    if (!empty($session_val)) {
        $query .= " AND session = ?";
        $params[] = $session_val;
        $types .= "s";
    }

    $query .= " ORDER BY session DESC, term DESC, subject ASC";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error preparing results query: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        die("Error executing results query: " . $stmt->error);
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results_for_pdf[] = $row;
    }
    $stmt->close();
}

// Build HTML for PDF
$html = '
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
        .student-info { margin-bottom: 20px; }
        .passport-img { float: right; }
    </style>
    <h2>Student Academic Results</h2>
    <table class="student-info">
        <tr>
            <td width="80%">
                <p><strong>Student Name:</strong> ' . htmlspecialchars($full_name) . '</p>
                <p><strong>Login ID (Unique):</strong> ' . htmlspecialchars($student_unique_id) . '</p>
                <p><strong>Student ID (Official):</strong> ' . htmlspecialchars($official_student_id) . '</p>
                <p><strong>Class:</strong> ' . htmlspecialchars($class_assigned) . '</p>
            </td>
            <td width="20%" style="text-align:right;">';

if (!empty($passport_src)) {
    $html .= '<img src="' . $passport_src . '" width="100" height="100" style="border:1px solid #000;" class="passport-img" />';
}

$html .= '</td>
        </tr>
    </table>
    <hr>';

if (!empty($results_for_pdf)) {
    $html .= '<table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Session</th>
                        <th>Assessment</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Final Score</th>
                        <th>Result Date</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($results_for_pdf as $row) {
        $assessment = is_numeric($row['assessments']) ? $row['assessments'] : 0;
        $exam = is_numeric($row['exam_score']) ? $row['exam_score'] : 0;
        $total = $assessment + $exam;
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['subject']) . '</td>
                    <td>' . htmlspecialchars($row['term']) . '</td>
                    <td>' . htmlspecialchars($row['session']) . '</td>
                    <td>' . htmlspecialchars($assessment) . '</td>
                    <td>' . htmlspecialchars($exam) . '</td>
                    <td><strong>' . htmlspecialchars($total) . '</strong></td>
                    <td>' . htmlspecialchars($row['final_score'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['result_date'] ?? '') . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p style="text-align:center;">No approved results available for you at the moment.</p>';
}

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Enable loading remote images (for passport)
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("student_results_" . htmlspecialchars($student_unique_id) . ".pdf", ["Attachment" => false]);
exit;