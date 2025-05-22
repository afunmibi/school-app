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

// Fetch the student's registration_id from the students table
$stmt = $conn->prepare("SELECT registration_id FROM students WHERE unique_id = ?");
if (!$stmt) {
    die("Error preparing query: " . $conn->error);
}
$stmt->bind_param("s", $student_unique_id);
$stmt->execute();
$stmt->bind_result($student_registration_id);
$stmt->fetch();
$stmt->close();

// Get and validate filters from GET parameters
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';
// Validate term
$valid_terms = ['First Term', 'Second Term', 'Third Term'];
if (!empty($term) && !in_array($term, $valid_terms)) {
    $term = '';
}
// Validate session format (e.g., 2024/2025)
if (!empty($session_val) && !preg_match('/^\d{4}\/\d{4}$/', $session_val)) {
    $session_val = '';
}

if ($student_registration_id) {
    // Fetch data from final_exam_results
    $query = "SELECT subject, term, session, assessments, exam_score, final_score, full_name, class, result_date 
              FROM final_exam_results 
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
    if (!$stmt) {
        die("Error preparing query: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        die("Error executing query: " . $stmt->error);
    }
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results_data[] = [
            'subject' => $row['subject'],
            'term' => $row['term'],
            'session' => $row['session'],
            'assessments' => $row['assessments'],
            'exam_score' => $row['exam_score'],
            'final_score' => $row['final_score'],
            'full_name' => $row['full_name'],
            'class' => $row['class'],
            'result_date' => $row['result_date']
        ];
    }
    $stmt->close();
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
        <?php if ($student_registration_id && !empty($results_data)): ?>
            <h5 class="text-center">Student: <?= htmlspecialchars($results_data[0]['full_name']) ?> | Class: <?= htmlspecialchars($results_data[0]['class']) ?></h5>
        <?php endif; ?>
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
                            <th scope="col">Final Score</th>
                            <th scope="col">Result Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results_data as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['term']) ?></td>
                                <td><?= htmlspecialchars($row['session']) ?></td>
                                <td><?= htmlspecialchars($row['assessments'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['exam_score'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $total = (is_numeric($row['assessments']) ? $row['assessments'] : 0) + (is_numeric($row['exam_score']) ? $row['exam_score'] : 0);
                                    echo $total > 0 ? htmlspecialchars($total) : '';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['final_score'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['result_date'] ?? '') ?></td>
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