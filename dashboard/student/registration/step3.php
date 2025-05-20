<?php
session_start();
include "../../../config.php";

$unique_id = $_SESSION['student_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data from students table
$query = "SELECT * FROM students WHERE unique_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_step3'])) {
    $parent_name = trim($_POST['parent_name']);
    $parent_address = trim($_POST['parent_address']);
    $parent_occupation = trim($_POST['parent_occupation']);
    $child_comment = trim($_POST['child_comment']);
    $religion = trim($_POST['religion']);

    // Handle file uploads with validation
    $upload_dir = "../../../uploads/students/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Birth Certificate
    $birth_certificate_path = $student_data['birth_certificate'] ?? '';
    if (isset($_FILES['birth_certificate']) && $_FILES['birth_certificate']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['birth_certificate']['tmp_name']);
        $file_size = $_FILES['birth_certificate']['size'];
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            $ext = strtolower(pathinfo($_FILES['birth_certificate']['name'], PATHINFO_EXTENSION));
            $birth_certificate_path = $upload_dir . uniqid("birth_") . '.' . $ext;
            move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $birth_certificate_path);
        } else {
            $message = "Invalid birth certificate file type or size.";
        }
    }

    // Testimonial
    $testimonial_path = $student_data['testimonial'] ?? '';
    if (isset($_FILES['testimonial']) && $_FILES['testimonial']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['testimonial']['tmp_name']);
        $file_size = $_FILES['testimonial']['size'];
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            $ext = strtolower(pathinfo($_FILES['testimonial']['name'], PATHINFO_EXTENSION));
            $testimonial_path = $upload_dir . uniqid("testimonial_") . '.' . $ext;
            move_uploaded_file($_FILES['testimonial']['tmp_name'], $testimonial_path);
        } else {
            $message = "Invalid testimonial file type or size.";
        }
    }

    // Only update if no file errors
    if (empty($message)) {
        $update = $conn->prepare("UPDATE students SET parent_name=?, parent_address=?, parent_occupation=?, child_comment=?, religion=?, birth_certificate=?, testimonial=? WHERE unique_id=?");
        $update->bind_param("ssssssss", $parent_name, $parent_address, $parent_occupation, $child_comment, $religion, $birth_certificate_path, $testimonial_path, $unique_id);
        $update->execute();
        $update->close();

        // Generate PDF (unique ID)
        require_once '../../../vendor/autoload.php';
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml("<h1>Registration Complete</h1><p>Your Unique ID: " . htmlspecialchars($student_data['unique_id']) . "</p>");
        $dompdf->render();
        $output = $dompdf->output();
        $pdf_dir = "../../../pdfs/";
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0777, true);
        file_put_contents($pdf_dir . 'student_' . $student_data['unique_id'] . '.pdf', $output);

        header("Location: success.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Registration - Step 3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Student Full Registration - Step 3</h4>
            <?php if ($message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="parent_name">Parent's Name</label>
                    <input type="text" name="parent_name" class="form-control" value="<?= htmlspecialchars($student_data['parent_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="parent_address">Parent's Address</label>
                    <input type="text" name="parent_address" class="form-control" value="<?= htmlspecialchars($student_data['parent_address'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="parent_occupation">Parent's Occupation</label>
                    <input type="text" name="parent_occupation" class="form-control" value="<?= htmlspecialchars($student_data['parent_occupation'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="child_comment">Comment on the Child</label>
                    <textarea name="child_comment" class="form-control" rows="4" required><?= htmlspecialchars($student_data['child_comment'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="religion">Religion</label>
                    <input type="text" name="religion" class="form-control" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="birth_certificate">Birth Certificate</label>
                    <input type="file" name="birth_certificate" class="form-control" <?= empty($student_data['birth_certificate']) ? 'required' : '' ?>>
                    <?php if (!empty($student_data['birth_certificate'])): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($student_data['birth_certificate']) ?>" target="_blank">View Uploaded</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="testimonial">Testimonial (if applicable)</label>
                    <input type="file" name="testimonial" class="form-control">
                    <?php if (!empty($student_data['testimonial'])): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($student_data['testimonial']) ?>" target="_blank">View Uploaded</a>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="submit_step3" class="btn btn-primary w-100">Complete Registration</button>
            </form>
        </div>
    </div>
</body>
</html>