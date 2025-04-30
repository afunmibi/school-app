<?php
session_start();
include "../config.php";

// Redirect if not logged in as student or no student session
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch student pre-registration data
$student_id = $_SESSION['student_id'];
$query = "SELECT * FROM pre_registration WHERE id = (SELECT pre_reg_id FROM student_login WHERE student_id = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_step3'])) {
    $parent_name = $_POST['parent_name'];
    $parent_address = $_POST['parent_address'];
    $parent_occupation = $_POST['parent_occupation'];
    $comment_on_child = $_POST['comment_on_child'];
    $religion = $_POST['religion'];
    $birth_certificate = $_FILES['birth_certificate']['name'];
    $testimonial = $_FILES['testimonial']['name'];

    // Upload documents
    $birth_certificate_path = 'uploads/' . basename($birth_certificate);
    move_uploaded_file($_FILES['birth_certificate']['tmp_name'], $birth_certificate_path);

    $testimonial_path = 'uploads/' . basename($testimonial);
    move_uploaded_file($_FILES['testimonial']['tmp_name'], $testimonial_path);

    // Save parent and document info
    $update_parent_info = $conn->prepare("UPDATE pre_registration SET parent_name = ?, parent_address = ?, parent_occupation = ?, comment_on_child = ?, religion = ?, birth_certificate = ?, testimonial = ? WHERE id = ?");
    $update_parent_info->bind_param("sssssssi", $parent_name, $parent_address, $parent_occupation, $comment_on_child, $religion, $birth_certificate_path, $testimonial_path, $student_data['id']);
    $update_parent_info->execute();
    
    // Generate PDF (unique ID)
    require_once '../dompdf/autoload.inc.php';
    $dompdf = new Dompdf();
    $dompdf->loadHtml("<h1>Registration Complete</h1><p>Your Unique ID: " . $student_data['unique_id'] . "</p>");
    $dompdf->render();
    $output = $dompdf->output();
    file_put_contents('../pdfs/student_' . $student_data['unique_id'] . '.pdf', $output);

    header("Location: success.php");
    exit;
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
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="parent_name">Parent's Name</label>
                    <input type="text" name="parent_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="parent_address">Parent's Address</label>
                    <input type="text" name="parent_address" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="parent_occupation">Parent's Occupation</label>
                    <input type="text" name="parent_occupation" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="comment_on_child">Comment on the Child</label>
                    <textarea name="comment_on_child" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="religion">Religion</label>
                    <input type="text" name="religion" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="birth_certificate">Birth Certificate</label>
                    <input type="file" name="birth_certificate" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="testimonial">Testimonial (if applicable)</label>
                    <input type="file" name="testimonial" class="form-control">
                </div>
                <button type="submit" name="submit_step3" class="btn btn-primary w-100">Complete Registration</button>
            </form>
        </div>
    </div>
</body>
</html>
