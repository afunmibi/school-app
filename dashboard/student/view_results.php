<?php
session_start();
// echo "Session Unique ID: " . htmlspecialchars($_SESSION['student_unique_id']);
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
$full_name = ""; // Initialize full_name

// Fetch student info (full_name) from final_exam_results (limit 1) using unique_id
$stmt_name = $conn->prepare("SELECT full_name FROM final_exam_results WHERE unique_id = ? LIMIT 1");
if ($stmt_name) {
    $stmt_name->bind_param("s", $student_unique_id);
    $stmt_name->execute();
    $res_name = $stmt_name->get_result();
    $student = $res_name->fetch_assoc();
    $stmt_name->close();
    if ($student) {
        $full_name = $student['full_name'];
    } else {
        echo "<div class='alert alert-danger mt-5 text-center'>Student record not found.</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger mt-5 text-center'>Error preparing statement for student name.</div>";
    exit;
}

// Get filter values from GET params
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';

// Prepare query to fetch results with filters using unique_id
$query = "SELECT * FROM final_exam_results WHERE unique_id = ?";
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
}

$stmt_results = $conn->prepare($query);
if ($stmt_results) {
    $stmt_results->bind_param($types, ...$params);
    $stmt_results->execute();
    $results = $stmt_results->get_result();
    $stmt_results->close();
} else {
    echo "<div class='alert alert-danger mt-5 text-center'>Error preparing filtered results statement.</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Student Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Results for <?= htmlspecialchars($full_name) ?></h4>
        <div>
            <a href="export_results_excel.php<?= !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-success btn-sm">Export to Excel</a>
            <a href="generate_result_pdf.php<?= !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-danger btn-sm">Download PDF</a>
        </div>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <h5 class="mb-3">Filter Results by Term and Session</h5>

        <div class="col-md-4">
            <label class="form-label">Term</label>
            <select name="term" class="form-select">
                <option value="">-- All Terms --</option>
                <option value="First Term" <?= ($term == 'First Term') ? 'selected' : '' ?>>First Term</option>
                <option value="Second Term" <?= ($term == 'Second Term') ? 'selected' : '' ?>>Second Term</option>
                <option value="Third Term" <?= ($term == 'Third Term') ? 'selected' : '' ?>>Third Term</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Session</label>
            <input type="text" name="session" value="<?= htmlspecialchars($session_val) ?>" class="form-control" placeholder="e.g., 2024/2025" />
        </div>

        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Subject</th>
                    <th>Assessment</th>
                    <th>Exam Score</th>
                    <th>Final Score</th>
                    <th>Term</th>
                    <th>Session</th>
                    <th>Class</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= htmlspecialchars($row['assessments']) ?></td>
                        <td><?= htmlspecialchars($row['exam_score']) ?></td>
                        <td><?= htmlspecialchars($row['final_score']) ?></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><?= htmlspecialchars($row['session']) ?></td>
                        <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                        <td><?= htmlspecialchars($row['result_date']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-danger">No results found. Try selecting different Term or Session.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm me-2">Dashboard</a>
    <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
</div>
</body>
</html>