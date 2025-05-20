<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only allow teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

require '../../vendor/autoload.php'; // Correct path for Composer autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get filters (class, start date, end date)
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Query to fetch students' data based on class and date filters
$query = "SELECT 
            students.full_name, 
            students.class_assigned AS class,
            assessments.assessment_score,
            exam_scores.score AS exam_score,
            exam_scores.date_recorded AS result_date
          FROM students
          LEFT JOIN assessments ON students.id = assessments.student_id
          LEFT JOIN exam_scores ON students.id = exam_scores.student_id";

$conditions = [];
$params = [];
$types = "";

// Build WHERE clause dynamically
if ($class_filter) {
    $conditions[] = "students.class_assigned = ?";
    $params[] = $class_filter;
    $types .= "s";
}
if ($start_date && $end_date) {
    $conditions[] = "exam_scores.date_recorded BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}
if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
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
    $assessment_score = is_numeric($data['assessment_score']) ? $data['assessment_score'] : 0;
    $exam_score = is_numeric($data['exam_score']) ? $data['exam_score'] : 0;
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