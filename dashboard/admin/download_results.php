<?php
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require '../vendor/autoload.php';  // If you're using Composer. Otherwise, include PhpSpreadsheet manually.

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Redirect if not logged in as teacher or admin
if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get the user type (admin or teacher)
$user_type = isset($_SESSION['admin_id']) ? 'admin' : 'teacher';

// Define the table to fetch from based on user type
$table = $user_type == 'admin' ? 'exam_results' : 'assessments';

// Fetch data from the database
$query = "SELECT students.full_name, assessments.assessment_score, exam_results.exam_score
          FROM students
          LEFT JOIN assessments ON students.id = assessments.student_id
          LEFT JOIN exam_results ON students.id = exam_results.student_id";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Full Name')
      ->setCellValue('B1', 'Continuous Assessment Score')
      ->setCellValue('C1', 'Examination Score');

// Write data to the spreadsheet
$row = 2;
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $data['full_name'])
          ->setCellValue('B' . $row, $data['assessment_score'] ?? 'N/A')
          ->setCellValue('C' . $row, $data['exam_score'] ?? 'N/A');
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
