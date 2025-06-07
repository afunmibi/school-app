<?php
// Enable strict error reporting for development.
declare(strict_types=1);

// Start the session.
session_start();

// Include the database configuration file.
require_once "../../config.php";

// Include the Dompdf library (adjust the path if necessary)
require_once('../../vendor/autoload.php');

// Check if the student is logged in. Redirect if not.
if (!isset($_SESSION['student_unique_id']) || empty($_SESSION['student_unique_id'])) {
    header("Location: student_login.php");
    exit;
}

$studentUniqueId = $_SESSION['student_unique_id'];
$studentFullName = '';
$studentClass = '';
$studentPassport = '';

// Fetch student details
$studentDetailsStmt = $conn->prepare("SELECT full_name, class_assigned, passport_photo FROM students WHERE unique_id = ?");
if ($studentDetailsStmt) {
    $studentDetailsStmt->bind_param("s", $studentUniqueId);
    $studentDetailsStmt->execute();
    $studentDetailsResult = $studentDetailsStmt->get_result();
    if ($studentDetailsRow = $studentDetailsResult->fetch_assoc()) {
        $studentFullName = $studentDetailsRow['full_name'];
        $studentClass = $studentDetailsRow['class_assigned'];
        $studentPassport = $studentDetailsRow['passport_photo'];
    }
    $studentDetailsStmt->close();
} else {
    error_log("Generate PDF Error: Prepare query for student details failed: " . $conn->error);
    echo "<div class='alert alert-danger mt-3'>Error fetching student details for PDF.</div>";
    exit;
}

// Get filter values from GET params
$term = $_GET['term'] ?? '';
$sessionVal = $_GET['session'] ?? '';

// Prepare query to fetch results with filters using unique_id
$query = "SELECT subject, assessments, exam_score, final_score, term, session, class_assigned, result_date FROM final_exam_results WHERE unique_id = ?";
$params = [$studentUniqueId];
$types = "s";

if (!empty($term)) {
    $query .= " AND term = ?";
    $types .= "s";
    $params[] = $term;
}

if (!empty($sessionVal)) {
    $query .= " AND session = ?";
    $types .= "s";
    $params[] = $sessionVal;
}

$stmtResults = $conn->prepare($query);
if ($stmtResults) {
    $stmtResults->bind_param($types, ...$params);
    $stmtResults->execute();
    $results = $stmtResults->get_result();
} else {
    echo "<div class='alert alert-danger mt-3'>Error preparing results statement for PDF.</div>";
    exit;
}

// Start buffering the output
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Results</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        h1 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .student-info { margin-bottom: 15px; display: flex; align-items: center; }
        .label { font-weight: bold; margin-right: 10px; }
        .passport-photo { width: 100px; height: 100px; border-radius: 5px; margin-right: 20px; object-fit: cover; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Student Results</h1>
    <div class="student-info">
        <?php
        $passportPath = '../../uploads/passports/' . htmlspecialchars($studentPassport);
        if (!empty($studentPassport) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $passportPath)): ?>
            <img src="<?= $passportPath ?>" alt="Student Passport" class="passport-photo">
        <?php else: ?>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($studentFullName) ?>&size=100" alt="Default Avatar" class="passport-photo">
        <?php endif; ?>
        <div>
            <div><span class="label">Name:</span> <?= htmlspecialchars($studentFullName) ?></div>
            <div><span class="label">Class:</span> <?= htmlspecialchars($studentClass) ?></div>
            <?php if (!empty($term)): ?><div><span class="label">Term:</span> <?= htmlspecialchars($term) ?></div><?php endif; ?>
            <?php if (!empty($sessionVal)): ?><div><span class="label">Session:</span> <?= htmlspecialchars($sessionVal) ?></div><?php endif; ?>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Assessment</th>
                <th>Exam Score</th>
                <th>Final Score</th>
                <th>Term</th>
                <th>Session</th>
                <th>Class</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['assessments']) ?></td>
                        <td><?= htmlspecialchars($row['exam_score']) ?></td>
                        <td><?= htmlspecialchars($row['final_score']) ?></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><?= htmlspecialchars($row['session']) ?></td>
                        <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                        <td><?= htmlspecialchars($row['result_date']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No results found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
// Get the buffered content
$html = ob_get_clean();

// Instantiate Dompdf class
$dompdf = new Dompdf\Dompdf();

// Set base path for Dompdf to access local files
$dompdf->setBasePath(realpath($_SERVER["DOCUMENT_ROOT"]));

// Load HTML content
$dompdf->loadHtml($html);

// (Optional) Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser (inline view)
$dompdf->stream("student_results.pdf", ["Attachment" => 1]); // 1 for download, 0 for inline

// Close database connection
if (isset($stmtResults)) {
    $stmtResults->close();
}
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>