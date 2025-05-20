<?php
session_start();
include "../../config.php";
require '../../vendor/autoload.php'; // DomPDF path

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Authenticate Student
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_unique_id = $_SESSION['student_id'];

// 2. Fetch Student Info
$stmt = $conn->prepare("SELECT registration_id, full_name, student_id AS official_student_id, class_assigned, passport_photo FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $student_unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    die("Error: Student details not found.");
}

$student_registration_id = $student_data['registration_id'];

// 3. Get Filters from URL (Term and Session are optional)
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';

// 4. Fetch Results for the logged-in student
$results = [];
$query = "SELECT subject, exam_score, assessment_score, term, session 
          FROM results 
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
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Calculate total score
        $row['total_score'] = (is_numeric($row['assessment_score']) ? $row['assessment_score'] : 0) + (is_numeric($row['exam_score']) ? $row['exam_score'] : 0);
        $results[] = $row;
    }
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}
$conn->close();

// 5. Build HTML for PDF
$html = '
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2, h3 { text-align: center; }
    .student-details-container { margin-bottom: 20px; border: 1px solid #ddd; padding: 10px; overflow: auto; }
    .student-text-details { float: left; width: 70%; }
    .student-details p { margin: 5px 0; }
    .student-photo { float: right; width: 25%; text-align: right; }
    .student-photo img { max-width: 100px; max-height: 120px; border: 1px solid #ccc; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .clearfix::after { content: ""; clear: both; display: table; }
    th { background-color: #f2f2f2; }
    .no-results { text-align: center; color: #777; margin-top: 30px; }
</style>
<h2>Student Academic Report</h2>';

$passport_photo_html = '';
if (!empty($student_data['passport_photo'])) {
    $image_server_path = dirname(dirname(dirname(__FILE__))) . '/uploads/passports/' . htmlspecialchars($student_data['passport_photo']);
    if (file_exists($image_server_path)) {
        $type = pathinfo($image_server_path, PATHINFO_EXTENSION);
        if (in_array(strtolower($type), ['jpg', 'jpeg', 'png', 'gif'])) {
            $data = file_get_contents($image_server_path);
            $base64_image = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $passport_photo_html = '<img src="' . $base64_image . '" alt="Student Photo">';
        }
    }
}

$html .= '<div class="student-details-container clearfix">';
$html .= '  <div class="student-text-details">';
$html .= '    <p><strong>Name:</strong> ' . htmlspecialchars($student_data['full_name']) . '</p>';
$html .= '    <p><strong>Student ID (Official):</strong> ' . htmlspecialchars($student_data['official_student_id']) . '</p>';
$html .= '    <p><strong>Class:</strong> ' . htmlspecialchars($student_data['class_assigned']) . '</p>';
if (!empty($term)) { $html .= '<p><strong>Term:</strong> ' . htmlspecialchars($term) . '</p>'; }
if (!empty($session_val)) { $html .= '<p><strong>Session:</strong> ' . htmlspecialchars($session_val) . '</p>'; }
$html .= '  </div>';
$html .= '  <div class="student-photo">' . $passport_photo_html . '</div>';
$html .= '</div>';

if (!empty($results)) {
    $html .= '
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Assessment Score</th>
                <th>Exam Score</th>
                <th>Total Score</th>
                <th>Term</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($results as $row) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['subject']) . '</td>
                    <td>' . htmlspecialchars($row['assessment_score']) . '</td>
                    <td>' . htmlspecialchars($row['exam_score']) . '</td>
                    <td><strong>' . htmlspecialchars($row['total_score']) . '</strong></td>
                    <td>' . htmlspecialchars($row['term']) . '</td>
                    <td>' . htmlspecialchars($row['session']) . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p class="no-results">No approved results found for the selected criteria.</p>';
}

// 6. Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "results_" . htmlspecialchars($student_data['official_student_id'] ?: $student_unique_id);
if (!empty($term)) $filename .= "_" . str_replace(' ', '_', $term);
if (!empty($session_val)) $filename .= "_" . str_replace('/', '-', $session_val);
$filename .= ".pdf";

$dompdf->stream($filename, ["Attachment" => false]);
exit;