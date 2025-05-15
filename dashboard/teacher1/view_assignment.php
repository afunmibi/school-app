<?php
session_start();
include "../../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
// Dynamically get the teacher's assigned class
$stmt = $conn->prepare("SELECT class_assigned FROM users WHERE id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($selected_class);
$stmt->fetch();
$stmt->close();

$search_query = $_GET['search'] ?? '';

// Fetch assignments for the teacher's class only
$sql = "SELECT * FROM assignments WHERE teacher_id = ? AND class = ?";
$params = [$teacher_id, $selected_class];
$types = "is";

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <title>My Posted Assignments</title>
</head>
<body>
<div class="container mt-5">
    <div class="bg-white p-4 rounded shadow">
        <h4 class="mb-4">My Posted Assignments (<?= htmlspecialchars($selected_class) ?>)</h4>
        <form method="GET" class="row mb-4">
            <div class="col-md-9">
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" class="form-control" placeholder="Search by title or subject">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
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
                    <th>Assignment File</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Submission File</th>
                    <th>Text Assignment</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $count = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Fetch all students in the teacher's class
                        $students = [];
                        $student_stmt = $conn->prepare("SELECT id, full_name, student_id FROM students WHERE class_assigned = ?");
                        $student_stmt->bind_param("s", $selected_class);
                        $student_stmt->execute();
                        $student_result = $student_stmt->get_result();
                        while ($student = $student_result->fetch_assoc()) {
                            $students[] = $student;
                        }
                        $student_stmt->close();

                        if (empty($students)) {
                            $students[] = ['id' => null, 'full_name' => 'N/A', 'student_id' => 'N/A'];
                        }
                        $first = true;
                        foreach ($students as $student):
                            // Fetch submission for this assignment and student
                            $submission_file = '';
                            $submission_text = '';
                            if ($student['id']) {
                                $sub_stmt = $conn->prepare("SELECT submission_file, submission_text FROM assignments WHERE id = ? AND student_id = ?");
                                $sub_stmt->bind_param("ii", $row['id'], $student['id']);
                                $sub_stmt->execute();
                                $sub_stmt->bind_result($submission_file, $submission_text);
                                $sub_stmt->fetch();
                                $sub_stmt->close();
                            }
                        ?>
                        <tr>
                            <?php if ($first): ?>
                                <td rowspan="<?= count($students) ?>"><?= $count++ ?></td>
                                <td rowspan="<?= count($students) ?>"><?= htmlspecialchars($row['class']) ?></td>
                                <td rowspan="<?= count($students) ?>"><?= htmlspecialchars($row['subject']) ?></td>
                                <td rowspan="<?= count($students) ?>"><?= htmlspecialchars($row['title']) ?></td>
                                <td rowspan="<?= count($students) ?>"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                <td rowspan="<?= count($students) ?>"><?= date("d M Y", strtotime($row['due_date'])) ?></td>
                                <td rowspan="<?= count($students) ?>"><?= date("d M Y", strtotime($row['date_posted'])) ?></td>
                                <td rowspan="<?= count($students) ?>">
                                    <?php if (!empty($row['submission_file'])): ?>
                                        <a href="../uploads/<?= htmlspecialchars($row['submission_file']) ?>" target="_blank">Download</a>
                                    <?php else: ?>
                                        No file
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td>
                                <?php if (!empty($submission_file)): ?>
                                    <a href="../student/<?= htmlspecialchars($submission_file) ?>" target="_blank" class="btn btn-sm btn-success mb-1">View/Download</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Submitted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($submission_text)): ?>
                                    <?= nl2br(htmlspecialchars($submission_text)) ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No Text</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $first = false; endforeach; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="12" class="text-center">No assignments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>