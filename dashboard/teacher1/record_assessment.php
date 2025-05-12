<?php
session_start();
include "../../config.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ✅ Only allow logged-in teachers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $student_id = trim($_POST['student_id']);
    $subject = trim($_POST['subject']);
    $score = intval($_POST['score']);
    $term = trim($_POST['term']);
    $session_year = trim($_POST['session']); // 'session' is a reserved word in PHP

    // Prepare SQL insert
    $stmt = $conn->prepare("INSERT INTO results (student_id, subject, score, term, session, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssiss", $student_id, $subject, $score, $term, $session_year);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Result submitted successfully and is pending admin approval.</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
        }
        .alert {
            margin-bottom: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
       
            .form-control {
                margin-bottom: 10px;
            }
            </style>
</head>
<body>
    <div class="container">
        <h2>Record Student Results</h2>
        
        <div class="alert alert-info" role="alert">
            Please fill in the details below to record student results.
        </div>
        <form method="POST" action="record_results.php">
    <select name="student_id" required>
        <option value="">Select Student</option>
        <?php
        $res = $conn->query("SELECT registration_id, full_name FROM students");
        while ($row = $res->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($row['registration_id']).'">'.htmlspecialchars($row['full_name']).' ('.$row['registration_id'].')</option>';
        }
        ?>
    </select>
    <input type="text" name="subject" placeholder="Subject" required>
    <input type="number" name="score" placeholder="Score" required>
    <input type="text" name="term" placeholder="Term (e.g. 1st Term)" required>
    <input type="text" name="session" placeholder="Session (e.g. 2024/2025)" required>
    <button type="submit" class="btn btn-primary btn-sm">Submit Result</button>
</form>
<a href="./dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
   
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
