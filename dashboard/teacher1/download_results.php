<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ✅ Ensure only teachers access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// ✅ Filters
$class_filter = trim($_GET['class'] ?? '');
$student_name_filter = trim($_GET['student_name'] ?? '');
$subject_filter = trim($_GET['subject'] ?? '');
$term_filter = trim($_GET['term'] ?? '');
$session_filter = trim($_GET['session'] ?? '');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

// ✅ Base query
$query = "SELECT full_name, class_assigned, subject, term, session, assessments, exam_score, final_score, result_date FROM final_exam_results";
$conditions = ["teacher_id = ?"];
$params = [$teacher_id];
$types = "i";

// ✅ Add optional filters
if ($class_filter) {
    $conditions[] = "class = ?";
    $params[] = $class_filter;
    $types .= "s";
}
if ($student_name_filter) {
    $conditions[] = "full_name LIKE ?";
    $params[] = "%$student_name_filter%";
    $types .= "s";
}
if ($subject_filter) {
    $conditions[] = "subject = ?";
    $params[] = $subject_filter;
    $types .= "s";
}
if ($term_filter) {
    $conditions[] = "term = ?";
    $params[] = $term_filter;
    $types .= "s";
}
if ($session_filter) {
    $conditions[] = "session = ?";
    $params[] = $session_filter;
    $types .= "s";
}
if ($start_date && $end_date) {
    $conditions[] = "DATE(result_date) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif ($start_date) {
    $conditions[] = "DATE(result_date) >= ?";
    $params[] = $start_date;
    $types .= "s";
} elseif ($end_date) {
    $conditions[] = "DATE(result_date) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// ✅ Finalize query
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY full_name ASC, class_assigned ASC, subject ASC";

// ✅ Prepare and bind
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Query preparation failed: " . $conn->error);
    exit("Error preparing query.");
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ✅ Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Student Results');

$sheet->setCellValue('A1', 'Full Name')
      ->setCellValue('B1', 'Class')
      ->setCellValue('C1', 'Subject')
      ->setCellValue('D1', 'Term')
      ->setCellValue('E1', 'Session')
      ->setCellValue('F1', 'CA Score')
      ->setCellValue('G1', 'Exam Score')
      ->setCellValue('H1', 'Final Score')
      ->setCellValue('I1', 'Result Date');

$row_num = 2;
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row_num", $data['full_name'])
          ->setCellValue("B$row_num", $data['class'])
          ->setCellValue("C$row_num", $data['subject'])
          ->setCellValue("D$row_num", $data['term'])
          ->setCellValue("E$row_num", $data['session'])
          ->setCellValue("F$row_num", is_numeric($data['assessments']) ? $data['assessments'] : 0)
          ->setCellValue("G$row_num", is_numeric($data['exam_score']) ? $data['exam_score'] : 0)
          ->setCellValue("H$row_num", is_numeric($data['final_score']) ? $data['final_score'] : 0)
          ->setCellValue("I$row_num", $data['result_date'] ? date('Y-m-d', strtotime($data['result_date'])) : '');
    $row_num++;
}

$stmt->close();
$conn->close();

// ✅ Output as Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="students_results.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
