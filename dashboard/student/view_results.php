<?php
session_start();
include "../../config.php";

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$student_unique_id = $_SESSION['student_id'];
$student_registration_id = null;
$results_data = [];

// Fetch the student's registration_id using their unique_id from the session
$stmt = $conn->prepare("SELECT registration_id FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $student_unique_id);
$stmt->execute();
$stmt->bind_result($student_registration_id);
$stmt->fetch();
$stmt->close();

// Get filters from GET parameters
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';

if ($student_registration_id) {
    // Fetch assessment and exam scores from 'results' table
    $query = "SELECT subject, term, session, assessment_score, exam_score 
              FROM results 
              WHERE student_id = ? AND status = 'approved'";
    $params = [$student_registration_id];
    $types = "s";

    if (!empty($term)) {
        $query .= " AND term = ?";
        $params[] = $term;
        $types .= "s";
    }
    if (!empty($session_val)) {
        $query .= " AND session = ?";
        $params[] = $session_val;
        $types .= "s";
    }

    $query .= " ORDER BY session DESC, term DESC, subject ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Collect results by subject for merging with final_exam_results
    $subject_results = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row['subject'] . '|' . $row['term'] . '|' . $row['session'];
        $subject_results[$key] = [
            'subject' => $row['subject'],
            'term' => $row['term'],
            'session' => $row['session'],
            'assessment_score' => $row['assessment_score'],
            'exam_score' => $row['exam_score'],
            'final_exam_result' => null // to be filled from final_exam_results table
        ];
    }
    $stmt->close();

    // Fetch final exam results from 'final_exam_results' table
    $query2 = "SELECT subject, term, session, final_score 
               FROM final_exam_results 
               WHERE student_id = ?";
    $params2 = [$student_registration_id];
    $types2 = "s";
    if (!empty($term)) {
        $query2 .= " AND term = ?";
        $params2[] = $term;
        $types2 .= "s";
    }
    if (!empty($session_val)) {
        $query2 .= " AND session = ?";
        $params2[] = $session_val;
        $types2 .= "s";
    }
    $query2 .= " ORDER BY session DESC, term DESC, subject ASC";
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param($types2, ...$params2);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    while ($row2 = $result2->fetch_assoc()) {
        $key = $row2['subject'] . '|' . $row2['term'] . '|' . $row2['session'];
        if (isset($subject_results[$key])) {
            $subject_results[$key]['final_exam_result'] = $row2['final_score'];
        } else {
            // If no assessment/exam score, still show final result
            $subject_results[$key] = [
                'subject' => $row2['subject'],
                'term' => $row2['term'],
                'session' => $row2['session'],
                'assessment_score' => null,
                'exam_score' => null,
                'final_exam_result' => $row2['final_score']
            ];
        }
    }
    $stmt2->close();

    // Prepare for display
    $results_data = array_values($subject_results);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-10 offset-md-1 bg-white shadow p-4 rounded">
        <h4 class="text-center text-primary mb-4">Your Approved Results</h4>
        <form method="get" class="row g-3 mb-3">
            <div class="col-md-4">
                <select name="term" class="form-control">
                    <option value="">-- Select Term --</option>
                    <option value="First Term" <?= $term == 'First Term' ? 'selected' : '' ?>>First Term</option>
                    <option value="Second Term" <?= $term == 'Second Term' ? 'selected' : '' ?>>Second Term</option>
                    <option value="Third Term" <?= $term == 'Third Term' ? 'selected' : '' ?>>Third Term</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="session" class="form-control" placeholder="Session (e.g. 2024/2025)" value="<?= htmlspecialchars($session_val) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
        <div class="text-center mb-3">
            <a href="download_results.php<?= ($term || $session_val) ? '?term=' . urlencode($term) . '&session=' . urlencode($session_val) : '' ?>" target="_blank" class="btn btn-info">Download All Results Summary (PDF)</a>
        </div>
        <?php if (!empty($results_data)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th scope="col">Subject</th>
                            <th scope="col">Term</th>
                            <th scope="col">Session</th>
                            <th scope="col">Assessment Score</th>
                            <th scope="col">Exam Score</th>
                            <th scope="col">Total Score</th>
                            <th scope="col">Final Exam Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['term']) ?></td>
                                <td><?= htmlspecialchars($row['session']) ?></td>
                                <td><?= htmlspecialchars($row['assessment_score']) ?></td>
                                <td><?= htmlspecialchars($row['exam_score']) ?></td>
                                <td>
                                    <?php
                                    $total = (is_numeric($row['assessment_score']) ? $row['assessment_score'] : 0) + (is_numeric($row['exam_score']) ? $row['exam_score'] : 0);
                                    echo $total > 0 ? htmlspecialchars($total) : '';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['final_exam_result']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($student_registration_id === null): ?>
            <p class="alert alert-warning text-center">Your student profile could not be fully loaded. Please contact administration.</p>
        <?php else: ?>
            <p class="alert alert-info text-center">No approved results available for you at the moment.</p>
        <?php endif; ?>
        <hr>
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>