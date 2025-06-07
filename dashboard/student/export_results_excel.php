<?php
// Start the session to manage user login state.
session_start();

// Include the database configuration file.
require_once "../../config.php";

// Enable full error reporting for development.
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Enable MySQLi error reporting to throw exceptions on errors, aiding debugging.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Redirect if not logged in as student
if (!isset($_SESSION['student_unique_id'])) {
    header("Location: student_login.php");
    exit;
}

// Get student unique ID from session
$student_unique_id = $_SESSION['student_unique_id'];
$student_full_name = '';
$student_class = '';

// Fetch student details
$student_details_stmt = $conn->prepare("SELECT full_name, class_assigned FROM students WHERE unique_id = ?");
if ($student_details_stmt) {
    $student_details_stmt->bind_param("s", $student_unique_id);
    $student_details_stmt->execute();
    $student_details_result = $student_details_stmt->get_result();
    if ($student_details_row = $student_details_result->fetch_assoc()) {
        $student_full_name = $student_details_row['full_name'];
        $student_class = $student_details_row['class_assigned'];
    }
    $student_details_stmt->close();
} else {
    error_log("Export Results Error: Prepare query for student details failed: " . $conn->error);
}

// Get filter values from GET params
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';

// Prepare query to fetch results with filters using unique_id
$query = "SELECT subject, assessments, exam_score, final_score, term, session, class_assigned, result_date FROM final_exam_results WHERE unique_id = ?";
$params = [$student_unique_id];
$types = "s";

if (!empty($term)) {
    $query .= " AND term = ?";
    $types .= "s";
    $params[] = $term;
}

if (!empty($session_val)) {
    $query .= " AND session = ?";
    $types .= "s";
    $params[] = $term;
    $query .= " AND session = ?";
    $types .= "s";
    $params[] = $session_val;
}

$stmt_results = $conn->prepare($query);
if ($stmt_results) {
    $stmt_results->bind_param($types, ...$params);
    $stmt_results->execute();
    $results = $stmt_results->get_result();
} else {
    echo "<div class='alert alert-danger mt-5 text-center'>Error preparing results statement for export.</div>";
    exit;
}

// Set headers to force download of Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="student_results.xls"');
header('Cache-Control: max-age=0');

echo '<table border="1">';
echo '<thead><tr><th colspan="8" style="font-weight: bold; font-size: 1.2em;">Student Results</th></tr></thead>';
echo '<thead><tr><th colspan="2" style="font-weight: bold;">Name:</th><td colspan="6">' . htmlspecialchars($student_full_name) . '</td></tr></thead>';
echo '<thead><tr><th colspan="2" style="font-weight: bold;">Class:</th><td colspan="6">' . htmlspecialchars($student_class) . '</td></tr></thead>';
echo '<thead><tr><th colspan="8"></th></tr></thead>'; // Empty row for spacing
echo '<thead><tr><th>Subject</th><th>Assessment</th><th>Exam Score</th><th>Final Score</th><th>Term</th><th>Session</th><th>Class</th><th>Date</th></tr></thead>';
echo '<tbody>';

if ($results->num_rows > 0) {
    while ($row = $results->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
        echo '<td>' . htmlspecialchars($row['assessments']) . '</td>';
        echo '<td>' . htmlspecialchars($row['exam_score']) . '</td>';
        echo '<td>' . htmlspecialchars($row['final_score']) . '</td>';
        echo '<td>' . htmlspecialchars($row['term']) . '</td>';
        echo '<td>' . htmlspecialchars($row['session']) . '</td>';
        echo '<td>' . htmlspecialchars($row['class_assigned']) . '</td>';
        echo '<td>' . htmlspecialchars($row['result_date']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="8">No results found.</td></tr>';
}

echo '</tbody>';
echo '</table>';

if (isset($stmt_results)) {
    $stmt_results->close();
}
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>