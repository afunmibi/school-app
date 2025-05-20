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
    $passport_path = '../../uploads/passports/' . $passport;
    if (file_exists($passport_path)) {
        $mime = mime_content_type($passport_path);
        $passport_data = base64_encode(file_get_contents($passport_path));
        $passport_src = 'data:' . $mime . ';base64,' . $passport_data;
    }
}

// Fetch approved results
if ($student_registration_id) {
    $stmt = $conn->prepare("SELECT subject, term, session, assessment_score, exam_score 
                            FROM results 
                            WHERE student_id = ? AND status = 'approved'
                            ORDER BY session DESC, term DESC, subject ASC");
    $stmt->bind_param("s", $student_registration_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results_for_pdf[] = $row;
    }
    $stmt->close();
}

// Build HTML for PDF
$html = '
    <h2 style="text-align:center;">Student Academic Results</h2>
    <table width="100%" style="margin-bottom:20px;">
        <tr>
            <td width="80%">
                <p><strong>Student Name:</strong> ' . htmlspecialchars($full_name) . '</p>
                <p><strong>Login ID (Unique):</strong> ' . htmlspecialchars($student_unique_id) . '</p>
                <p><strong>Student ID (Official):</strong> ' . htmlspecialchars($official_student_id) . '</p>
                <p><strong>Class:</strong> ' . htmlspecialchars($class_assigned) . '</p>
            </td>
            <td width="20%" style="text-align:right;">';

if (!empty($passport_src)) {
    $html .= '<img src="' . $passport_src . '" width="100" height="100" style="border:1px solid #000;" />';
}

$html .= '</td>
        </tr>
    </table>
    <hr>
';

if (!empty($results_for_pdf)) {
    $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Session</th>
                        <th>Assessment</th>
                        <th>Exam</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($results_for_pdf as $row) {
        $assessment = is_numeric($row['assessment_score']) ? $row['assessment_score'] : 0;
        $exam = is_numeric($row['exam_score']) ? $row['exam_score'] : 0;
        $total = $assessment + $exam;
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['subject']) . '</td>
                    <td>' . htmlspecialchars($row['term']) . '</td>
                    <td>' . htmlspecialchars($row['session']) . '</td>
                    <td>' . htmlspecialchars($assessment) . '</td>
                    <td>' . htmlspecialchars($exam) . '</td>
                    <td><strong>' . htmlspecialchars($total) . '</strong></td>
                  </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p style="text-align:center;">No approved results available for you at the moment.</p>';
}

// Setup DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("student_results_" . htmlspecialchars($student_unique_id) . ".pdf", ["Attachment" => false]);
exit;