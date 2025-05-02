<?php
require '../../vendor/autoload.php'; // DomPDF path

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
include "../../config.php";

if (!isset($_SESSION['student_id'])) {
    die("Unauthorized access.");
}

$student_id = $_SESSION['student_id'];

// Get student full name
$stmt = $conn->prepare("SELECT full_name FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$full_name = $student['full_name'];

// Fetch approved exam results
$query = "SELECT exam_score FROM exam_results WHERE student_id = (SELECT id FROM students WHERE student_id = ?) AND status = 'approved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$html = '
    <h2 style="text-align:center;">Student Exam Results</h2>
    <p><strong>Student Name:</strong> ' . htmlspecialchars($full_name) . '</p>
    <p><strong>Student ID:</strong> ' . htmlspecialchars($student_id) . '</p>
    <hr>
';

if ($result->num_rows > 0) {
    $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>';
    $i = 1;
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . htmlspecialchars($row['exam_score']) . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';
} else {
    $html .= '<p>No approved results found.</p>';
}

// Setup DomPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optional) Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render and stream the PDF
$dompdf->render();
$dompdf->stream("exam_results_" . $student_id . ".pdf", ["Attachment" => false]);

// Get student full name and passport
$stmt = $conn->prepare("SELECT full_name, passport_photo FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$full_name = $student['full_name'];
$passport = $student['passport_photo']; // This should be the filename only

$passport_path = '../../uploads/passports/' . $passport;

if (file_exists($passport_path)) {
    $passport_data = base64_encode(file_get_contents($passport_path));
    $passport_src = 'data:image/jpeg;base64,' . $passport_data;
} else {
    $passport_src = '';
}

exit;
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
$html = '
    <h2 style="text-align:center;">Student Exam Results</h2>
    <table width="100%" style="margin-bottom:20px;">
        <tr>
            <td width="80%">
                <p><strong>Student Name:</strong> ' . htmlspecialchars($full_name) . '</p>
                <p><strong>Student ID:</strong> ' . htmlspecialchars($student_id) . '</p>
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
 
</body>
</html>