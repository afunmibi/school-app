<?php

session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Redirect if not logged in as teacher or admin
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch data: sum of assessments and exam scores per student
$query = "SELECT 
            students.full_name, 
            COALESCE(SUM(assessments.assessment_score), 0) AS assessments, 
            COALESCE(SUM(final_exam_results.exam_score), 0) AS exam_scores,
            (COALESCE(SUM(assessments.assessment_score), 0) + COALESCE(SUM(final_exam_results.exam_score), 0)) AS final_exam_results
          FROM students
          LEFT JOIN assessments ON students.id = assessments.student_id
          LEFT JOIN final_exam_results ON students.id = final_exam_results.student_id
          GROUP BY students.id, students.full_name";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Full Name')
      ->setCellValue('B1', 'Assessments')
      ->setCellValue('C1', 'Exam Scores')
      ->setCellValue('D1', 'Final Exam Results');

// Write data to the spreadsheet
$row = 2;
while ($data = $result->fetch_assoc()) {
    $assessments = is_numeric($data['assessments']) ? $data['assessments'] : 0;
    $exam_scores = is_numeric($data['exam_scores']) ? $data['exam_scores'] : 0;
    $final_exam_results = is_numeric($data['final_exam_results']) ? $data['final_exam_results'] : 0;

    $sheet->setCellValue('A' . $row, $data['full_name'])
          ->setCellValue('B' . $row, $assessments)
          ->setCellValue('C' . $row, $exam_scores)
          ->setCellValue('D' . $row, $final_exam_results);
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