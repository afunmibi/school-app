<?php
session_start();
include "./config.php";
require './vendor/autoload.php';  // If you're using Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if logged in as teacher or admin
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get class filter if set
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';

// Query to fetch students' data based on class filter
$query = "SELECT students.full_name, students.class, 
                 assessments.assessment_score, 
                 exam_results.exam_score
          FROM students
          LEFT JOIN assessments ON students.id = assessments.student_id
          LEFT JOIN exam_results ON students.id = exam_results.student_id";

if ($class_filter) {
    $query .= " WHERE students.class = ?";
}

$stmt = $conn->prepare($query);

if ($class_filter) {
    $stmt->bind_param("s", $class_filter);
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
      ->setCellValue('E1', 'Total Score');

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
          ->setCellValue('E' . $row, number_format($total_score, 2));
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
