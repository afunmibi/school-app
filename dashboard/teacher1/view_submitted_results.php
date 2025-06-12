<?php
session_start();
include "../../config.php";

// Role-based access control
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'student'])) {
    header("Location: ../../index.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$term = $_GET['term'] ?? '';
$session_val = $_GET['session'] ?? '';
$action = $_GET['action'] ?? '';

// Excel Export
if ($action === 'export') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=submitted_results.xls");
    echo "Student Name\tClass\tSubject\tAssessment\tExam\tTotal\tStatus\tTerm\tSession\n";
    
    $query = "SELECT full_name, subject, assessments, exam_score, final_score, status, term, session, class
              FROM final_exam_results
              WHERE " . ($role === 'teacher' ? "teacher_id = ?" : "student_id = ?");

    if ($term) $query .= " AND term = ?";
    if ($session_val) $query .= " AND session = ?";

    $stmt = $conn->prepare($query);
    if ($term && $session_val) {
        $stmt->bind_param("iss", $user_id, $term, $session_val);
    } elseif ($term || $session_val) {
        $stmt->bind_param("is", $user_id, $term ?: $session_val);
    } else {
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "{$row['full_name']}\t{$row['class']}\t{$row['subject']}\t{$row['assessments']}\t{$row['exam_score']}\t{$row['final_score']}\t{$row['status']}\t{$row['term']}\t{$row['session']}\n";
    }
    exit;
}

// Fetch Results
$query = "SELECT id, full_name, subject, assessments, exam_score, final_score, status, term, session, class_assigned
          FROM final_exam_results
          WHERE " . ($role === 'teacher' ? "teacher_id = ?" : "student_id = ?");

if ($term) $query .= " AND term = ?";
if ($session_val) $query .= " AND session = ?";

$stmt = $conn->prepare($query);
if ($term && $session_val) {
    $stmt->bind_param("iss", $user_id, $term, $session_val);
} elseif ($term || $session_val) {
    $stmt->bind_param("is", $user_id, $term ?: $session_val);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submitted Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3 class="text-center mb-4"><?= $role === 'teacher' ? 'My Submitted Results' : 'My Academic Results' ?></h3>

    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <input type="text" name="term" class="form-control" placeholder="Filter by Term (e.g. 1st Term)" value="<?= htmlspecialchars($term) ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="session" class="form-control" placeholder="Filter by Session (e.g. 2023/2024)" value="<?= htmlspecialchars($session_val) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Apply Filter</button>
        </div>
        <div class="col-md-2">
            <a href="?term=<?= $term ?>&session=<?= $session_val ?>&action=export" class="btn btn-success w-100">Export Excel</a>
        </div>
    </form>

    <?php if (count($results) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Assessment</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Term</th>
                    <th>Session</th>
                    <?php if ($role === 'teacher'): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td><?= $row['assessments'] ?></td>
                        <td><?= $row['exam_score'] ?></td>
                        <td><strong><?= $row['final_score'] ?></strong></td>
                        <td><span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : 'warning' ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><?= htmlspecialchars($row['session']) ?></td>
                        <?php if ($role === 'teacher'): ?>
                            <td>
                                <a href="../admin/edit_result.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="../admin/delete_result.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this result?')">Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">No results found for the selected filters.</div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
