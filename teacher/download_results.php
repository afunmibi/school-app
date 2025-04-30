<?php
session_start();
include "../config.php";
require '../vendor/autoload.php';  // If you're using Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if logged in as teacher or admin
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get filters (class, start date, end date)
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Query to fetch students' data based on class and date filters
$query = "SELECT students.full_name, students.class, 
                 assessments.assessment_score, 
                 exam_results.exam_score, exam_results.result_date
          FROM students
          LEFT JOIN assessments ON students.id = assessments.student_id
          LEFT JOIN exam_results ON students.id = exam_results.student_id";

if ($class_filter) {
    $query .= " WHERE students.class = ?";
}

if ($start_date && $end_date) {
    $query .= ($class_filter ? " AND" : " WHERE") . " exam_results.result_date BETWEEN ? AND ?";
}

$stmt = $conn->prepare($query);

// Bind parameters based on the filters
if ($class_filter && $start_date && $end_date) {
    $stmt->bind_param("sss", $class_filter, $start_date, $end_date);
} elseif ($class_filter) {
    $stmt->bind_param("s", $class_filter);
} elseif ($start_date && $end_date) {
    $stmt->bind_param("ss", $start_date, $end_date);
}

$stmt->execute();
$result = $stmt->get_result();

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Full Name')
      ->setCellValue('B1', 'Class')
      ->setCellValue('C1', 'Continuous Assessment Score')
      ->setCellValue('D1', 'Examination Score')
      ->setCellValue('E1', 'Total Score')
      ->setCellValue('F1', 'Result Date');

// Write data to the spreadsheet
$row = 2;
while ($data = $result->fetch_assoc()) {
    // Calculate total score (30% assessment, 70% exam)
    $assessment_score = $data['assessment_score'] ?? 0;
    $exam_score = $data['exam_score'] ?? 0;
    $total_score = ($assessment_score * 0.3) + ($exam_score * 0.7);

    $sheet->setCellValue('A' . $row, $data['full_name'])
          ->setCellValue('B' . $row, $data['class'])
          ->setCellValue('C' . $row, $assessment_score)
          ->setCellValue('D' . $row, $exam_score)
          ->setCellValue('E' . $row, number_format($total_score, 2))
          ->setCellValue('F' . $row, $data['result_date']);
    $row++;
}

// Set the header to trigger file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="students_results.xlsx"');
header('Cache-Control: max-age=0');

// Create Excel file and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
