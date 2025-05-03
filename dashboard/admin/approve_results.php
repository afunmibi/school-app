<?php
session_start();
include "../../config.php";

// Ensure only admin can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Approve logic
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];
    $stmt = $conn->prepare("UPDATE results SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: approve_results.php");
    exit;
}

// Fetch pending results
$results = $conn->query("SELECT * FROM results WHERE status = 'pending' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approve Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h4 class="text-primary">Pending Results for Approval</h4>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Subject</th>
                <th>Score</th>
                <th>Term</th>
                <th>Session</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= htmlspecialchars($row['score']) ?></td>
                    <td><?= htmlspecialchars($row['term']) ?></td>
                    <td><?= htmlspecialchars($row['session']) ?></td>
                    <td>
                        <a href="?approve_id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
