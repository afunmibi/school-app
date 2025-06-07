<?php
session_start();

include "../../../config.php";
require_once '../../../vendor/autoload.php';

use Dompdf\Dompdf;

// Ensure student is logged in
$unique_id = $_SESSION['student_unique_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data
$query = "SELECT *, DATE_FORMAT(dob, '%Y-%m-%d') AS dob FROM students WHERE unique_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}

// Define school information (you might want to retrieve this again or pass from the main script)
$schoolName = "Calvary Arrows College";
$schoolAddress = "KM 21, Aliade Road, Gboko, Benue State";
$schoolMotto = "Polished after the similitude of a palace";
$schoolTel = "+234 808 486 5689";
$examDate = "17th May, 2025";

// Capture the HTML from the session if you decide to pass it,
// or you can reconstruct it here by including the download_admission_letter.php content
$html = $_SESSION['admission_letter_html'] ?? '';

if (!empty($html)) {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('admission_letter_' . $student_data['unique_id'] . '.pdf', ['Attachment' => true]);
    exit();
} else {
    // Handle case where HTML is not available (e.g., direct access to this file)
    echo "<div class='alert alert-danger'>Error: Admission letter content not found.</div>";
}
?>