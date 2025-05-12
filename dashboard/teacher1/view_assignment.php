<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$selected_class = $_GET['class'] ?? '';
$search_query = $_GET['search'] ?? '';

// Fetch classes for dropdown
$class_stmt = $conn->prepare("SELECT DISTINCT class FROM assignments WHERE teacher_id = ?");
$class_stmt->bind_param("i", $teacher_id);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

// Build dynamic query
$sql = "SELECT * FROM assignments WHERE teacher_id = ?";
$params = [$teacher_id];
$types = "i";

if (!empty($selected_class)) {
    $sql .= " AND class = ?";
    $params[] = $selected_class;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR subject LIKE ?)";
    $search_term = "%" . $search_query . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY date_posted DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Document</title>
</head>
<body>
    


<div class="container mt-5">
    <div class="bg-white p-4 rounded shadow">
        <h4 class="mb-4">My Posted Assignments</h4>

        <!-- Filter and Search -->
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <select name="class" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Classes --</option>
                    <?php while ($row = $class_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['class']) ?>" <?= ($selected_class === $row['class']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['class']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" class="form-control" placeholder="Search by title or subject">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <!-- Assignment Table -->
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Date Posted</th>
                    <th>File</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $count = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                            <td><?= date("d M Y", strtotime($row['due_date'])) ?></td>
                            <td><?= date("d M Y", strtotime($row['date_posted'])) ?></td>
                            <td>
                                <?php if (!empty($row['submission_file'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($row['submission_file']) ?>" target="_blank">Download</a>
                                <?php else: ?>
                                    No file
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No assignments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
