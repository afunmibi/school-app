<?php
session_start();
include "../../../config.php";

// Ensure student is logged in
$unique_id = $_SESSION['student_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

$step = $_POST['step'] ?? 1;
$message = "";

// Step 1: Student details
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 1) {
    $_SESSION['reg_address'] = trim($_POST['address']);
    $_SESSION['reg_age'] = intval($_POST['age']);
    $_SESSION['reg_state_of_origin'] = trim($_POST['state_of_origin']);
    $_SESSION['reg_lga_origin'] = trim($_POST['lga_origin']);
    $_SESSION['reg_state_of_residence'] = trim($_POST['state_of_residence']);
    $_SESSION['reg_lga_of_residence'] = trim($_POST['lga_of_residence']);
    $step = 2;
}

// Step 2: Parent details
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 2) {
    $_SESSION['reg_parent_name'] = trim($_POST['parent_name']);
    $_SESSION['reg_parent_address'] = trim($_POST['parent_address']);
    $_SESSION['reg_parent_occupation'] = trim($_POST['parent_occupation']);
    $_SESSION['reg_religion'] = trim($_POST['religion']);
    $_SESSION['reg_child_comment'] = trim($_POST['child_comment']);
    $step = 3;
}

// Step 3: File uploads and password, update only students table
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 3) {
    $upload_dir = "../../../uploads/students/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // File validation settings
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

    // Passport Photo
    $passport_path = $student_data['passport_photo'] ?? '';
    if (isset($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
        $file_type = mime_content_type($_FILES['passport']['tmp_name']);
        $file_size = $_FILES['passport']['size'];
        if (in_array($file_type, ['image/jpeg', 'image/png']) && $file_size <= $max_size) {
            $ext = strtolower(pathinfo($_FILES['passport']['name'], PATHINFO_EXTENSION));
            $passport_path = $upload_dir . uniqid("passport_") . '.' . $ext;
            move_uploaded_file($_FILES['passport']['tmp_name'], $passport_path);
        } else {
            $message = "Invalid passport photo file type or size.";
        }
    }

    // Password
    $password = $_POST['password'] ?? '';
    $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : ($student_data['password'] ?? '');

    // Only update if no file errors
    if (empty($message)) {
        $update = $conn->prepare("UPDATE students SET address=?, age=?, state_of_origin=?, lga_origin=?, state_of_residence=?, lga_of_residence=?, parent_name=?, parent_address=?, parent_occupation=?, religion=?, child_comment=?, birth_certificate=?, testimonial=?, passport_photo=?, password=? WHERE unique_id=?");
        $update->bind_param(
            "sissssssssssssss",
            $_SESSION['reg_address'],
            $_SESSION['reg_age'],
            $_SESSION['reg_state_of_origin'],
            $_SESSION['reg_lga_origin'],
            $_SESSION['reg_state_of_residence'],
            $_SESSION['reg_lga_of_residence'],
            $_SESSION['reg_parent_name'],
            $_SESSION['reg_parent_address'],
            $_SESSION['reg_parent_occupation'],
            $_SESSION['reg_religion'],
            $_SESSION['reg_child_comment'],
            $birth_certificate_path,
            $testimonial_path,
            $passport_path,
            $password_hash,
            $unique_id
        );
        $update->execute();
        $update->close();

        // Clear session vars for registration
        unset(
            $_SESSION['reg_address'], $_SESSION['reg_age'], $_SESSION['reg_state_of_origin'],
            $_SESSION['reg_lga_origin'], $_SESSION['reg_state_of_residence'], $_SESSION['reg_lga_of_residence'],
            $_SESSION['reg_parent_name'], $_SESSION['reg_parent_address'], $_SESSION['reg_parent_occupation'],
            $_SESSION['reg_religion'], $_SESSION['reg_child_comment']
        );

        header("Location: step3.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Full Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Full Registration</h4>
        <?php if ($message) echo "<div class='alert alert-danger'>$message</div>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($step == 1): ?>
                <input type="hidden" name="step" value="1">
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($student_data['address'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" class="form-control" value="<?= htmlspecialchars($student_data['age'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="state_of_origin">State of Origin</label>
                        <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" value="<?= htmlspecialchars($student_data['state_of_origin'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="lga_origin">LGA of Origin</label>
                        <input type="text" id="lga_origin" name="lga_origin" class="form-control" value="<?= htmlspecialchars($student_data['lga_origin'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="state_of_residence">State of Residence</label>
                        <input type="text" id="state_of_residence" name="state_of_residence" class="form-control" value="<?= htmlspecialchars($student_data['state_of_residence'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="lga_of_residence">LGA of Residence</label>
                        <input type="text" id="lga_of_residence" name="lga_of_residence" class="form-control" value="<?= htmlspecialchars($student_data['lga_of_residence'] ?? '') ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Next</button>
            <?php elseif ($step == 2): ?>
                <input type="hidden" name="step" value="2">
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="parent_name">Parent Name</label>
                        <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?= htmlspecialchars($student_data['parent_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="parent_address">Parent Address</label>
                        <input type="text" id="parent_address" name="parent_address" class="form-control" value="<?= htmlspecialchars($student_data['parent_address'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="parent_occupation">Parent Occupation</label>
                        <input type="text" id="parent_occupation" name="parent_occupation" class="form-control" value="<?= htmlspecialchars($student_data['parent_occupation'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="religion">Religion</label>
                        <input type="text" id="religion" name="religion" class="form-control" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="child_comment">Child Comment</label>
                        <input type="text" id="child_comment" name="child_comment" class="form-control" value="<?= htmlspecialchars($student_data['child_comment'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Next</button>
            <?php elseif ($step == 3): ?>
                <input type="hidden" name="step" value="3">
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="birth_certificate">Birth Certificate (PDF/JPG/PNG)</label>
                        <input type="file" id="birth_certificate" name="birth_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (!empty($student_data['birth_certificate'])): ?>
                            <div class="mt-2">
                                <a href="<?= htmlspecialchars($student_data['birth_certificate']) ?>" target="_blank">View Uploaded</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="testimonial">Testimonial (PDF/JPG/PNG)</label>
                        <input type="file" id="testimonial" name="testimonial" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (!empty($student_data['testimonial'])): ?>
                            <div class="mt-2">
                                <a href="<?= htmlspecialchars($student_data['testimonial']) ?>" target="_blank">View Uploaded</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="passport">Passport Photo</label>
                        <input type="file" id="passport" name="passport" class="form-control" accept=".jpg,.jpeg,.png">
                        <?php if (!empty($student_data['passport_photo'])): ?>
                            <div class="mt-2">
                                <img src="<?= htmlspecialchars($student_data['passport_photo']) ?>" alt="Passport" style="max-width:100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" value="">
                        <small class="text-muted">Leave blank to keep current password.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100">Finish Registration</button>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>