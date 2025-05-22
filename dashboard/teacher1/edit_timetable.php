<?php
session_start();
include "../../config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}
$teacher_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT class_assigned FROM teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($class);
$stmt->fetch();
$stmt->close();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = [];
$start = strtotime("07:30");
$end = strtotime("15:00");
while ($start < $end) {
    $periods[] = [date("H:i", $start), date("H:i", strtotime("+40 minutes", $start))];
    $start = strtotime("+40 minutes", $start);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("REPLACE INTO weekly_timetable (class, teacher_id, day, start_time, end_time, subject) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($days as $day) {
        foreach ($periods as $index => [$start_time, $end_time]) {
            $key = strtolower($day) . '_' . $index;
            $subject = $_POST[$key] ?? '';
            $stmt->bind_param("sissss", $class, $teacher_id, $day, $start_time, $end_time, $subject);
            $stmt->execute();
        }
    }
    $stmt->close();
    $message = "✅ Timetable updated successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Weekly Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="container">
    <h3 class="mb-4 text-primary">Edit Weekly Timetable (<?= htmlspecialchars($class) ?>)</h3>
    <?php if (!empty($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
    <form method="POST">
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <?php foreach ($days as $d): ?>
                            <th><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periods as $i => [$start, $end]): ?>
                        <tr>
                            <td><?= $start ?> - <?= $end ?></td>
                            <?php foreach ($days as $d): ?>
                                <td>
                                    <input type="text" name="<?= strtolower($d) . "_$i" ?>" class="form-control" placeholder="Subject">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-success">Save Timetable</button>
        <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
