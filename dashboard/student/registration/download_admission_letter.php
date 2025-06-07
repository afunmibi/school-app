<?php
session_start();
include "../../../config.php";
require_once '../../../vendor/autoload.php';

use Dompdf\Dompdf;

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../../logs/admission_letter_errors.log');

// Ensure student is logged in
$unique_id = $_SESSION['student_unique_id'] ?? null;
if (!$unique_id) {
    error_log("Admission Letter: No student_unique_id found in session.");
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data
$query = "SELECT full_name, dob, sex, state_of_origin, lga_origin, tribe, town_of_residence, address, postal_address, schools_attended, exam_center, present_class, parent_name, parent_marital_status, parent_occupation, parent_email, parent_phone, born_again, born_again_year, church_affiliation, church_position, parent_relationship, lived_together_duration, health_challenges, emotional_behavior, spiritual_behavior, social_behavior FROM students WHERE unique_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    error_log("Admission Letter: Student record not found for unique_id: $unique_id");
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}

// Define school information
$schoolName = "Calvary Arrows College";
$schoolAddress = "KM 21, Aliade Road, Gboko, Benue State";
$schoolMotto = "Polished after the similitude of a palace";
$schoolTel = "+234 808 486 5689";
$examDate = "17th May, 2025";
$admissionYear = "2025/2026";

// Start output buffering
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($schoolName) ?> - Admission Letter</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12pt;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #003087;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 80px;
            vertical-align: middle;
        }
        .header h1 {
            font-size: 24pt;
            color: #003087;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        .header p {
            font-size: 10pt;
            color: #555;
            margin: 2px 0;
        }
        .subheader {
            text-align: center;
            margin-bottom: 30px;
        }
        .subheader h2 {
            font-size: 18pt;
            color: #003087;
            margin-bottom: 10px;
        }
        .subheader .note {
            font-size: 10pt;
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 5px;
            display: inline-block;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            font-size: 14pt;
            color: #003087;
            border-left: 4px solid #003087;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        .detail-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .detail-item {
            flex: 1;
            min-width: 200px;
            font-size: 10pt;
            margin-bottom: 5px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .detail-value {
            color: #333;
        }
        .signature {
            margin-top: 40px;
            text-align: center;
            font-size: 10pt;
            color: #555;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 10px auto;
        }
        .footer {
            text-align: center;
            font-size: 9pt;
            color: #777;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .unique-id {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 10pt;
            color: #003087;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="unique-id">Unique ID: <?= htmlspecialchars($student_data['unique_id']) ?></div>
        <header class="header">
            <img src="file://<?php echo realpath('../../../assets/images/logo.png'); ?>" alt="Calvary Arrows College Logo">
            <h1><?= htmlspecialchars($schoolName) ?></h1>
            <p><?= htmlspecialchars($schoolAddress) ?></p>
            <p><strong>Motto:</strong> <?= htmlspecialchars($schoolMotto) ?></p>
            <p><strong>Tel:</strong> <?= htmlspecialchars($schoolTel) ?></p>
        </header>

        <div class="subheader">
            <h2>Admission Application for <?= htmlspecialchars($admissionYear) ?></h2>
            <p class="note">
                <strong>Important:</strong> Please bring a photocopy of this letter to the examination hall on <strong><?= htmlspecialchars($examDate) ?></strong>. Keep the original safe for your records.
            </p>
        </div>

        <section class="section">
            <h3>Candidate Information</h3>
            <div class="detail-row">
                <div class="detail-item"><span class="detail-label">Full Name:</span> <span class="detail-value"><?= htmlspecialchars($student_data['full_name']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Date of Birth:</span> <span class="detail-value"><?= htmlspecialchars($student_data['dob']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Sex:</span> <span class="detail-value"><?= htmlspecialchars($student_data['sex']) ?></span></div>
                <div class="detail-item"><span class="detail-label">State of Origin:</span> <span class="detail-value"><?= htmlspecialchars($student_data['state_of_origin']) ?></span></div>
                <div class="detail-item"><span class="detail-label">LGA:</span> <span class="detail-value"><?= htmlspecialchars($student_data['lga_origin']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Tribe:</span> <span class="detail-value"><?= htmlspecialchars($student_data['tribe']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Town of Residence:</span> <span class="detail-value"><?= htmlspecialchars($student_data['town_of_residence']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Home Address:</span> <span class="detail-value"><?= htmlspecialchars($student_data['address']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Postal Address:</span> <span class="detail-value"><?= htmlspecialchars($student_data['postal_address']) ?></span></div>
                <div class="detail-item"><span class="detail-label">School(s) Attended:</span> <span class="detail-value"><?= htmlspecialchars($student_data['schools_attended']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Examination Center:</span> <span class="detail-value"><?= htmlspecialchars($student_data['exam_center']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Present Class:</span> <span class="detail-value"><?= htmlspecialchars($student_data['present_class']) ?></span></div>
            </div>
        </section>

        <section class="section">
            <h3>Parent/Guardian Information</h3>
            <div class="detail-row">
                <div class="detail-item"><span class="detail-label">Name:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_name']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Marital Status:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_marital_status']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Occupation:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_occupation']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Email:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_email']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Phone Number:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_phone']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Born Again:</span> <span class="detail-value"><?= htmlspecialchars($student_data['born_again']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Year Born Again:</span> <span class="detail-value"><?= htmlspecialchars($student_data['born_again_year']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Church Affiliation:</span> <span class="detail-value"><?= htmlspecialchars($student_data['church_affiliation']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Church Position:</span> <span class="detail-value"><?= htmlspecialchars($student_data['church_position']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Relationship to Candidate:</span> <span class="detail-value"><?= htmlspecialchars($student_data['parent_relationship']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Duration Lived Together:</span> <span class="detail-value"><?= htmlspecialchars($student_data['lived_together_duration']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Health Challenges:</span> <span class="detail-value"><?= htmlspecialchars($student_data['health_challenges']) ?></span></div>
            </div>
        </section>

        <section class="section">
            <h3>Child's Behavioral Assessment</h3>
            <div class="detail-row">
                <div class="detail-item"><span class="detail-label">Emotional Behavior:</span> <span class="detail-value"><?= htmlspecialchars($student_data['emotional_behavior']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Spiritual Behavior:</span> <span class="detail-value"><?= htmlspecialchars($student_data['spiritual_behavior']) ?></span></div>
                <div class="detail-item"><span class="detail-label">Social Behavior:</span> <span class="detail-value"><?= htmlspecialchars($student_data['social_behavior']) ?></span></div>
            </div>
        </section>

        <div class="signature">
            <p>Authorized Signature</p>
            <div class="signature-line"></div>
            <p>Date: <?= date('F j, Y') ?></p>
        </div>

        <footer class="footer">
            <p>Calvary Arrows College | <?= htmlspecialchars($schoolAddress) ?> | Tel: <?= htmlspecialchars($schoolTel) ?></p>
            <p>&copy; <?= date('Y') ?> All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>

<?php
$html = ob_get_clean();

$dompdf = new Dompdf(['enable_remote' => true]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save PDF to server
$pdf_dir = '../../../pdfs/';
if (!is_dir($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}
$pdf_path = $pdf_dir . 'admission_letter_' . $student_data['unique_id'] . '.pdf';
file_put_contents($pdf_path, $dompdf->output());
error_log("Admission Letter: PDF saved to $pdf_path");

// Stream PDF for download
$dompdf->stream('admission_letter_' . $student_data['unique_id'] . '.pdf', ['Attachment' => true]);
?>