<?php
session_start();
include "../../../config.php";

// Enable strict error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

$unique_id = null;
$is_admin_registration = false;

// Check if admin is logged in and a student unique ID is passed
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin' && isset($_GET['student_unique_id'])) {
    $unique_id = $_GET['student_unique_id'];
    $is_admin_registration = true;
} elseif (isset($_SESSION['student_unique_id'])) {
    // Ensure student is logged in
    $unique_id = $_SESSION['student_unique_id'];
} else {
    header("Location: ../../../login.php");
    exit;
}

// Fetch existing student data from database
$stmt = $conn->prepare("SELECT * FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$student_data = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

// Pre-fetch session data as fallback
$session_data = [
    'parent_name' => $_SESSION['reg_parent_name'] ?? $student_data['parent_name'] ?? '',
    'parent_address' => $_SESSION['reg_parent_address'] ?? $student_data['parent_address'] ?? '',
    'parent_occupation' => $_SESSION['reg_parent_occupation'] ?? $student_data['parent_occupation'] ?? '',
    'religion' => $_SESSION['reg_religion'] ?? $student_data['religion'] ?? '',
    'child_comment' => $_SESSION['reg_child_comment'] ?? $student_data['child_comment'] ?? '',
    'emotional_behavior' => $_SESSION['reg_emotional_behavior'] ?? $student_data['emotional_behavior'] ?? '',
    'spiritual_behavior' => $_SESSION['reg_spiritual_behavior'] ?? $student_data['spiritual_behavior'] ?? '',
    'social_behavior' => $_SESSION['reg_social_behavior'] ?? $student_data['social_behavior'] ?? ''
];



// Validate required session variables from previous steps
$required_session_vars = [
    'reg_address', 'reg_dob', 'reg_state_of_origin', 'reg_lga_origin', 'reg_state_of_residence',
    'reg_lga_of_residence', 'reg_sex', 'reg_tribe', 'reg_town_of_residence', 'reg_schools_attended',
    'reg_exam_center', 'reg_present_class', 'reg_parent_marital_status', 'reg_parent_email',
    'reg_parent_phone', 'reg_born_again', 'reg_born_again_year', 'reg_church_affiliation',
    'reg_church_position', 'reg_parent_relationship', 'reg_lived_together_duration',
    'reg_health_challenges', 'reg_postal_address', 'reg_parent_name', 'reg_parent_address',
    'reg_parent_occupation', 'reg_religion', 'reg_child_comment'
];

// Check for missing session variables and redirect
foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        $message = "<div class='alert alert-danger'>Missing required data: $var. Please complete previous steps. <a href='step1.php' class='alert-link'>Return to Step 1</a></div>";
        error_log("Step 3: Missing session variable: $var");
        header("Location: step1.php?error=" . urlencode("Missing required data: $var"));
        exit;
    }
}

// Handle PDF download request
if (isset($_GET['action']) && $_GET['action'] === 'download_pdf' && $unique_id) {
    generateRegistrationPDF($student_data, $conn, true);
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_step3'])) {
    // Validate required fields
    $required_fields = ['parent_name', 'parent_address', 'parent_occupation', 'religion'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "The $field field is required.";
        }
    }

    // Validate password (if provided)
    $password = $_POST['password'] ?? '';
    if ($password && strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // Sanitize inputs
    $parent_name = sanitize_input($_POST['parent_name'] ?? '');
    $parent_address = sanitize_input($_POST['parent_address'] ?? '');
    $parent_occupation = sanitize_input($_POST['parent_occupation'] ?? '');
    $child_comment = sanitize_input($_POST['child_comment'] ?? '');
    $religion = sanitize_input($_POST['religion'] ?? '');
    $emotional_behavior = sanitize_input($_POST['emotional_behavior'] ?? '');
    $spiritual_behavior = sanitize_input($_POST['spiritual_behavior'] ?? '');
    $social_behavior = sanitize_input($_POST['social_behavior'] ?? '');
    $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : ($student_data['password'] ?? '');

    // Handle file uploads
    $upload_dir = "../../../Uploads/students/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $birth_certificate_path = handleFileUpload('birth_certificate', $student_data['birth_certificate'] ?? '', $upload_dir, $allowed_types, $max_size, $message);
    $testimonial_path = handleFileUpload('testimonial', $student_data['testimonial'] ?? '', $upload_dir, $allowed_types, $max_size, $message);
    $passport_path = handleFileUpload('passport', $student_data['passport_photo'] ?? '', $upload_dir, ['image/jpeg', 'image/png'], $max_size, $message);

    if (empty($errors) && empty($message)) {
        $update = $conn->prepare("UPDATE students SET parent_name=?, parent_address=?, parent_occupation=?, child_comment=?, religion=?, birth_certificate=?, testimonial=?, passport_photo=?, password=?, address=?, dob=?, state_of_origin=?, lga_origin=?, state_of_residence=?, lga_of_residence=?, sex=?, tribe=?, town_of_residence=?, schools_attended=?, exam_center=?, present_class=?, parent_marital_status=?, parent_email=?, parent_phone=?, born_again=?, born_again_year=?, church_affiliation=?, church_position=?, parent_relationship=?, lived_together_duration=?, health_challenges=?, emotional_behavior=?, spiritual_behavior=?, social_behavior=?, postal_address=? WHERE unique_id=?");
        $update->bind_param(
            "ssssssssssssssssssssssssssssssssssss",
            $parent_name,
            $parent_address,
            $parent_occupation,
            $child_comment,
            $religion,
            $birth_certificate_path,
            $testimonial_path,
            $passport_path,
            $password_hash,
            $_SESSION['reg_address'],
            $_SESSION['reg_dob'],
            $_SESSION['reg_state_of_origin'],
            $_SESSION['reg_lga_origin'],
            $_SESSION['reg_state_of_residence'],
            $_SESSION['reg_lga_of_residence'],
            $_SESSION['reg_sex'],
            $_SESSION['reg_tribe'],
            $_SESSION['reg_town_of_residence'],
            $_SESSION['reg_schools_attended'],
            $_SESSION['reg_exam_center'],
            $_SESSION['reg_present_class'],
            $_SESSION['reg_parent_marital_status'],
            $_SESSION['reg_parent_email'],
            $_SESSION['reg_parent_phone'],
            $_SESSION['reg_born_again'],
            $_SESSION['reg_born_again_year'],
            $_SESSION['reg_church_affiliation'],
            $_SESSION['reg_church_position'],
            $_SESSION['reg_parent_relationship'],
            $_SESSION['reg_lived_together_duration'],
            $_SESSION['reg_health_challenges'],
            $emotional_behavior,
            $spiritual_behavior,
            $social_behavior,
            $_SESSION['reg_postal_address'],
            $unique_id
        );

        if ($update->execute()) {
            // Save PDF to server
            $pdf_path = generateRegistrationPDF($student_data, $conn, false);
            // Clear session variables
            foreach ($required_session_vars as $var) {
                unset($_SESSION[$var]);
            }
            header("Location: success.php?pdf_path=" . urlencode($pdf_path));
            exit;
        } else {
            $message = "<div class='alert alert-danger'>Error updating profile: " . htmlspecialchars($update->error) . "</div>";
            error_log("Step 3: Error updating student data: " . $update->error);
        }
        $update->close();
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . ($message ? "<br>$message" : "") . "</div>";
    }
}

// Helper function for sanitizing input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8Time: 01:14 AM WAT on Saturday, June 07, 2025');
}

// Helper function for handling file uploads
function handleFileUpload($fieldName, $existingPath, $uploadDir, $allowedTypes, $maxSize, &$message) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $fileSize = $_FILES[$fieldName]['size'];
        $fileName = $_FILES[$fieldName]['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($_FILES[$fieldName]['tmp_name']);

        error_log("Step 3: Detected Extension for $fieldName: $fileExt");
        error_log("Step 3: Detected Mime Type for $fieldName: $mimeType");
        error_log("Step 3: File Size for $fieldName: $fileSize");
        error_log("Step 3: Max Size Allowed: $maxSize");

        if ($fileSize > $maxSize) {
            $message = "<div class='alert alert-danger'>File size for " . str_replace('_', ' ', $fieldName) . " exceeds 2MB.</div>";
            return $existingPath;
        }

        if (!in_array($mimeType, $allowedTypes)) {
            $message = "<div class='alert alert-danger'>Invalid file type for " . str_replace('_', ' ', $fieldName) . ". Allowed types: " . implode(', ', $allowedTypes) . ".</div>";
            return $existingPath;
        }

        $newPath = $uploadDir . uniqid(strtolower($fieldName) . "_") . '.' . $fileExt;
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $newPath)) {
            return $newPath;
        } else {
            $message = "<div class='alert alert-danger'>Error uploading " . str_replace('_', ' ', $fieldName) . ". Please try again.</div>";
            error_log("Step 3: Error moving uploaded file for $fieldName: " . $_FILES[$fieldName]['error']);
        }
    }
    return $existingPath;
}

// Helper function to generate registration PDF
function generateRegistrationPDF($studentData, $conn, $stream = false) {
    $dompdf = new Dompdf();
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration Details</title>
    <style>
        body { font-family: Helvetica, sans-serif; font-size: 12px; margin: 20px; }
        h1 { color: #333; text-align: center; }
        .details { margin: 20px; }
        .details p { margin: 5px 0; }
        .section { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Student Registration Details</h1>
    <div class="section">
        <h2>Personal Information</h2>
        <div class="details">
            <p><strong>Name:</strong> {$studentData['name']}</p>
            <p><strong>Address:</strong> {$studentData['address']}</p>
            <p><strong>Date of Birth:</strong> {$studentData['dob']}</p>
            <p><strong>Sex:</strong> {$studentData['sex']}</p>
            <p><strong>Tribe:</strong> {$studentData['tribe']}</p>
        </div>
    </div>
    <div class="section">
        <h2>Parent/Guardian Information</h2>
        <div class="details">
            <p><strong>Parent Name:</strong> {$studentData['parent_name']}</p>
            <p><strong>Parent Address:</strong> {$studentData['parent_address']}</p>
            <p><strong>Parent Occupation:</strong> {$studentData['parent_occupation']}</p>
            <p><strong>Parent Email:</strong> {$studentData['parent_email']}</p>
            <p><strong>Parent Phone:</strong> {$studentData['parent_phone']}</p>
        </div>
    </div>
    <div class="section">
        <h2>Other Details</h2>
        <div class="details">
            <p><strong>Religion:</strong> {$studentData['religion']}</p>
            <p><strong>Child Comment:</strong> {$studentData['child_comment']}</p>
            <p><strong>Emotional Behavior:</strong> {$studentData['emotional_behavior']}</p>
            <p><strong>Spiritual Behavior:</strong> {$studentData['spiritual_behavior']}</p>
            <p><strong>Social Behavior:</strong> {$studentData['social_behavior']}</p>
        </div>
    </div>
</body>
</html>
HTML;

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save PDF to server
    $pdf_dir = '../../../pdfs/';
    if (!is_dir($pdf_dir)) {
        mkdir($pdf_dir, 0777, true);
    }
    $pdf_path = $pdf_dir . 'student_' . $studentData['unique_id'] . '.pdf';
    file_put_contents($pdf_path, $dompdf->output());
    error_log("Step 3: Registration PDF saved to $pdf_path");

    // Stream PDF for download if requested
    if ($stream) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="student_registration.pdf"');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        error_log("Step 3: Registration PDF streamed successfully for unique_id: " . $studentData['unique_id']);
        exit;
    }

    return $pdf_path;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Full Registration - Step 3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Full Registration - Step 3</h4>
        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="parent_name" class="form-label">Parent Name</label>
                    <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?= htmlspecialchars($session_data['parent_name']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_address" class="form-label">Parent Address</label>
                    <input type="text" id="parent_address" name="parent_address" class="form-control" value="<?= htmlspecialchars($session_data['parent_address']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_occupation" class="form-label">Parent Occupation</label>
                    <input type="text" id="parent_occupation" name="parent_occupation" class="form-control" value="<?= htmlspecialchars($session_data['parent_occupation']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="religion" class="form-label">Religion</label>
                    <input type="text" id="religion" name="religion" class="form-control" value="<?= htmlspecialchars($session_data['religion']) ?>" required>
                </div>
                <div class="mb-3 col-md-12">
                    <label for="child_comment" class="form-label">Child Comment</label>
                    <textarea id="child_comment" name="child_comment" class="form-control"><?= htmlspecialchars($session_data['child_comment']) ?></textarea>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="birth_certificate" class="form-label">Birth Certificate (PDF/JPG/PNG)</label>
                    <input type="file" id="birth_certificate" name="birth_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if (!empty($student_data['birth_certificate'])): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($student_data['birth_certificate'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">View Uploaded</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="testimonial" class="form-label">Testimonial (PDF/JPG/PNG)</label>
                    <input type="file" id="testimonial" name="testimonial" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if (!empty($student_data['testimonial'])): ?>
                        <div class="mt-2">
                            <a href="<?= htmlspecialchars($student_data['testimonial'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">View Uploaded</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="passport" class="form-label">Passport Photo (JPG/PNG)</label>
                    <input type="file" id="passport" name="passport" class="form-control" accept=".jpg,.jpeg,.png">
                    <?php if (!empty($student_data['passport_photo'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($student_data['passport_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Passport" style="max-width:100px;">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" value="">
                    <small class="text-muted">Leave blank to keep current password.</small>
                </div>
                <div class="mb-3 col-md-12">
                    <label for="emotional_behavior" class="form-label">Emotional Behavior</label>
                    <textarea id="emotional_behavior" name="emotional_behavior" class="form-control"><?= htmlspecialchars($session_data['emotional_behavior']) ?></textarea>
                </div>
                <div class="mb-3 col-md-12">
                    <label for="spiritual_behavior" class="form-label">Spiritual Behavior</label>
                    <textarea id="spiritual_behavior" name="spiritual_behavior" class="form-control"><?= htmlspecialchars($session_data['spiritual_behavior']) ?></textarea>
                </div>
                <div class="mb-3 col-md-12">
                    <label for="social_behavior" class="form-label">Social Behavior</label>
                    <textarea id="social_behavior" name="social_behavior" class="form-control"><?= htmlspecialchars($session_data['social_behavior']) ?></textarea>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success" name="submit_step3">Complete Registration</button>
                <?php if (file_exists("../../../pdfs/student_{$student_data['unique_id']}.pdf")): ?>
                    <a href="?action=download_pdf" class="btn btn-primary">Download PDF</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
</body>
</html>