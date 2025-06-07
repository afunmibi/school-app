<?php
session_start();
include "../../config.php";

// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Approve logic: update status and unique_id = student_id
if (isset($_GET['approve_id'])) {
    $id = intval($_GET['approve_id']);
   $stmt = $conn->prepare("
    UPDATE final_exam_results
    SET status = 'approved'
    WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: approve_results.php?approved=1");
        exit;
    } else {
        $error = "❌ Error approving result.";
    }
    $stmt->close();
}

// Reject logic
if (isset($_GET['reject_id'])) {
    $id = intval($_GET['reject_id']);
    $stmt = $conn->prepare("UPDATE final_exam_results SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: approve_results.php?rejected=1");
        exit;
    } else {
        $error = "❌ Error rejecting result.";
    }
    $stmt->close();
}

// Fetch all pending results
$results = $conn->query("SELECT * FROM final_exam_results WHERE status = 'pending' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approve Final Exam Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h4 class="text-primary">Pending Final Exam Results for Approval</h4>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['approved'])): ?>
        <div class="alert alert-success">✅ Result approved successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['rejected'])): ?>
        <div class="alert alert-warning">⚠️ Result rejected.</div>
    <?php endif; ?>

    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Subject</th>
                <th>CA</th>
                <th>Exam</th>
                <th>Final</th>
                <th>Term</th>
                <th>Session</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($results && $results->num_rows > 0): ?>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= htmlspecialchars($row['assessments']) ?></td>
                    <td><?= htmlspecialchars($row['exam_score']) ?></td>
                    <td><?= htmlspecialchars($row['final_score']) ?></td>
                    <td><?= htmlspecialchars($row['term']) ?></td>
                    <td><?= htmlspecialchars($row['session']) ?></td>
                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <a href="?approve_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this result?');">Approve</a>
                        <a href="?reject_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this result?');">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="text-center">No pending results found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
</body>
</html>

<?php $conn->close(); ?>
