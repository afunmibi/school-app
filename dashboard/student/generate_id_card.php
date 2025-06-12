<?php
// Enable strict error reporting
declare(strict_types=1);

// Start the session
session_start();

// Include the database configuration file
require_once "../../config.php";

// Include the Dompdf library
require_once '../../vendor/autoload.php';
use Dompdf\Dompdf;

// Check if the student is logged in; redirect if not
if (!isset($_SESSION['student_unique_id']) || empty($_SESSION['student_unique_id'])) {
    header("Location: student_login.php");
    exit;
}

$studentUniqueId = $_SESSION['student_unique_id'];
$studentFullName = '';
$studentClass = '';
$studentPassport = '';
$studentDOB = '';
$studentSex = '';
$studentAddress = '';
$issueDate = date("F j, Y", strtotime('2025-06-07 00:43:00')); // Current WAT date
$schoolName = "Your School Name"; // Replace with your school name

// Fetch student details
$studentDetailsStmt = $conn->prepare("SELECT full_name, class_assigned, passport_photo, dob, sex, address FROM students WHERE unique_id = ?");
if ($studentDetailsStmt) {
    $studentDetailsStmt->bind_param("s", $studentUniqueId);
    $studentDetailsStmt->execute();
    $studentDetailsResult = $studentDetailsStmt->get_result();
    if ($studentDetailsRow = $studentDetailsResult->fetch_assoc()) {
        $studentFullName = $studentDetailsRow['full_name'] ?? '';
        $studentClass = $studentDetailsRow['class_assigned'] ?? '';
        $studentPassport = $studentDetailsRow['passport_photo'] ?? '';
        $studentDOB = $studentDetailsRow['dob'] ?? '';
        $studentSex = $studentDetailsRow['sex'] ?? '';
        $studentAddress = $studentDetailsRow['address'] ?? '';
    } else {
        error_log("Generate ID Card Error: No student found for unique_id: $studentUniqueId");
        echo "<div class='alert alert-danger mt-3'>No student found.</div>";
        exit;
    }
    $studentDetailsStmt->close();
} else {
    error_log("Generate ID Card Error: Prepare query for student details failed: " . $conn->error);
    echo "<div class='alert alert-danger mt-3'>Error fetching student details for ID Card.</div>";
    exit;
}

// Ensure absolute path for passport photo
$basePath = $_SERVER['DOCUMENT_ROOT'] . '/Uploads/passport_photo/';
$passportPath = $studentPassport ? $basePath . $studentPassport : '';
if ($studentPassport && file_exists($passportPath)) {
    $passportSrc = 'file://' . realpath($passportPath);
    error_log("Generate ID Card: Passport photo found at $passportSrc");
} else {
    $passportSrc = 'https://via.placeholder.com/100x120?text=Photo';
    error_log("Generate ID Card: Passport photo not found at $passportPath, using placeholder");
}

// Clean output buffer
ob_end_clean();
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Identity Card</title>
    <style>
        @page {
            margin: 0;
            size: 85.60mm 53.98mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
        }
        .id-card {
            width: 3.375in; /* 85.60mm */
            height: 2.125in; /* 53.98mm */
            border: 1px solid #003087;
            border-radius: 8px;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .id-card-header {
            background-color: #003087;
            color: white;
            text-align: center;
            padding: 4px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        .school-name {
            font-size: 0.9em;
            font-weight: bold;
            margin: 0;
        }
        .id-title {
            font-size: 0.6em;
            margin: 0;
        }
        .id-card-body {
            display: flex;
            padding: 8px;
        }
        .photo-section {
            flex: 0 0 1.2in;
            text-align: center;
        }
        .photo-section img {
            width: 1.1in;
            height: 1.3in;
            object-fit: cover;
            border: 1px solid #003087;
            border-radius: 4px;
        }
        .photo-section .no-photo {
            font-size: 0.5em;
            color: red;
            margin-top: 2px;
        }
        .info-section {
            flex: 1;
            padding-left: 8px;
            font-size: 0.7em;
            color: #333;
        }
        .info-section p {
            margin: 2px 0;
        }
        .info-section .label {
            font-weight: bold;
        }
        .info-section .id-number {
            font-weight: bold;
            color: #003087;
        }
        .barcode-section {
            text-align: center;
            margin-top: 4px;
        }
        .barcode-section img {
            max-width: 1.8in;
            height: 0.4in;
        }
        .footer-section {
            position: absolute;
            bottom: 4px;
            width: 100%;
            text-align: center;
            font-size: 0.55em;
            color: #555;
        }
        .signature-section {
            position: absolute;
            bottom: 4px;
            right: 8px;
            font-size: 0.55em;
            font-style: italic;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="id-card-header">
            <h1 class="school-name"><?= htmlspecialchars($schoolName) ?></h1>
            <p class="id-title">Student Identity Card</p>
        </div>
        <div class="id-card-body">
            <div class="photo-section">
                <img src="uploads/<?= $passportSrc ?>" alt="Student Photo">
                <?php if ($passportSrc === 'https://via.placeholder.com/100x120?text=Photo'): ?>
                    <p class="no-photo">No photo available</p>
                <?php endif; ?>
            </div>
            <div class="info-section">
                <p><span class="label">Name:</span> <?= htmlspecialchars($studentFullName) ?></p>
                <p><span class="label">Class:</span> <?= htmlspecialchars($studentClass) ?></p>
                <p><span class="label">ID:</span> <span class="id-number"><?= htmlspecialchars($studentUniqueId) ?></span></p>
                <p><span class="label">Date of Birth:</span> <?= htmlspecialchars($studentDOB) ?></p>
                <p><span class="label">Sex:</span> <?= htmlspecialchars($studentSex) ?></p>
                <p><span class="label">Issue Date:</span> <?= htmlspecialchars($issueDate) ?></p>
            </div>
        </div>
        <div class="barcode-section">
            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= urlencode($studentUniqueId) ?>&code=Code128&dpi=96" alt="Barcode">
        </div>
        <div class="footer-section">
            Valid School Year: <?= date('Y') . '/' . (date('Y') + 1) ?>
        </div>
        <div class="signature-section">
            <p>___________________</p>
            <p>Authorized Signature</p>
        </div>
    </div>
</body>
</html>
<?php
// Get the buffered content
$html = ob_get_clean();

// Debug HTML content
error_log("Generate ID Card: HTML Content: " . substr($html, 0, 500) . "...");

// Save HTML for debugging
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/id_card_test.html', $html);

// Instantiate Dompdf class with options
$dompdf = new Dompdf([
    'isRemoteEnabled' => true,
    'dpi' => 300,
    'defaultFont' => 'Helvetica',
    'logOutputFile' => $_SERVER['DOCUMENT_ROOT'] . '/dompdf_log.html',
    'isFontSubsettingEnabled' => true,
]);

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size for standard ID card (85.60mm x 53.98mm)
$dompdf->setPaper(array(0, 0, 243.84, 153.12), 'landscape');

// Render the HTML as PDF
try {
    $dompdf->render();
    error_log("Generate ID Card: PDF rendering successful");
} catch (Exception $e) {
    error_log("Generate ID Card Error: PDF rendering failed: " . $e->getMessage());
    echo "<div class='alert alert-danger mt-3'>Error generating PDF: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Output the generated PDF to browser (inline for debugging)
$dompdf->stream("student_id_card_{$studentUniqueId}.pdf", ["Attachment" => 0]);

// Close database connection
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>