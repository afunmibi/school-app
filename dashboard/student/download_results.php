<?php
require '../../vendor/autoload.php';
use Dompdf\Dompdf;

include "../../config.php";
session_start();

// 🔐 Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    exit("Unauthorized access.");
}

$student_id = $_SESSION['student_id'];
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';

if (!$term || !$session_val) {
    exit("Invalid parameters.");
}

$stmt = $conn->prepare("SELECT subject, assessments, exam_score, final_score FROM final_exam_results WHERE student_id = ? AND term = ? AND session = ? AND status = 'approved'");
$stmt->bind_param("sss", $student_id, $term, $session_val);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$html = '<h2 style="text-align:center;">Approved Results</h2>';
$html .= "<p><strong>Student ID:</strong> {$student_id}</p>";
$html .= "<p><strong>Term:</strong> {$term} | <strong>Session:</strong> {$session_val}</p>";
$html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%">';
$html .= '<thead><tr><th>Subject</th><th>CA</th><th>Exam</th><th>Final</th></tr></thead><tbody>';
foreach ($data as $row) {
    $html .= "<tr>
                <td>{$row['subject']}</td>
                <td>{$row['assessments']}</td>
                <td>{$row['exam_score']}</td>
                <td>{$row['final_score']}</td>
              </tr>";
}
$html .= '</tbody></table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("result_{$student_id}_{$term}_{$session_val}.pdf", ["Attachment" => true]);
exit;
