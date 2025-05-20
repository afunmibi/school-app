<?php
session_start();
include "../../../config.php";

// Use unique_id from session for lookup
$unique_id = $_SESSION['student_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data using unique_id
$query = "SELECT * FROM students WHERE unique_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}

// Check if PDF exists
$pdf_path = '../pdfs/student_' . $student_data['unique_id'] . '.pdf';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Registration Completed Successfully</h4>
            <p class="text-center">
                Congratulations, your registration is complete! Your unique ID is:
                <strong><?= htmlspecialchars($student_data['unique_id']) ?></strong>
            </p>
            <p class="text-center">Click the link below to download your registration PDF:</p>
            <?php if (file_exists($pdf_path)) : ?>
                <p class="text-center">
                    <a href="<?= htmlspecialchars($pdf_path) ?>" class="btn btn-success" download title="Download your registration PDF">Download PDF</a>
                </p>
            <?php else: ?>
                <p class="text-center text-danger">PDF not generated yet. Please try again later.</p>
            <?php endif; ?>
            <p class="text-center"><a href="../../../logout.php" class="btn btn-danger">Logout</a></p>
        </div>
    </div>
</body>
</html>