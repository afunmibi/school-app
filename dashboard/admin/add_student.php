<?php
// Enable full error reporting for development.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Enable MySQLi error reporting to throw exceptions on errors, aiding debugging.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Start the session to manage user login state.
session_start();

// Include the database configuration file.
// Using 'require_once' ensures the file is included exactly once and halts execution if not found.
require_once "../../config.php"; // Adjust path as necessary

// Restrict access to administrators only.
// If the user is not logged in or their role is not 'admin', redirect them to the home page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit; // Terminate script execution after redirection.
}

$message = ""; // Initialize a variable to store feedback messages for the user.
$student = null; // Initialize a variable to hold student data, will be populated if editing.

// --- Handle initial page load (GET request) for editing a specific student ---
// Check if an 'id' is provided in the URL and if it's a valid number.
if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
    $student_db_id = (int)$_GET['id']; // Cast to integer for security and type consistency.

    // Prepare a statement to fetch student details from the 'students' table.
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    if ($stmt === false) {
        // If the prepare statement fails, output a database error and terminate.
        die("Database prepare error: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $student_db_id); // Bind the integer ID parameter.
    $stmt->execute(); // Execute the prepared statement.
    $result = $stmt->get_result(); // Get the result set.
    $student = $result->fetch_assoc(); // Fetch the single student record as an associative array.
    $stmt->close(); // Close the statement.

    // If no student is found with the given ID, set an error message.
    if (!$student) {
        $message .= "<div class='alert alert-danger'>Student not found for editing.</div>";
    }
}

// --- Handle form submission (POST request) for updating student information ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize all form inputs.
    $id_from_form          = $_POST['id'];
    $full_name             = trim($_POST['full_name'] ?? '');
    $email_address         = trim($_POST['email_address'] ?? '');
    $class_assigned        = trim($_POST['class_assigned'] ?? '');
    $student_id_form       = trim($_POST['student_id'] ?? '');
    $phone_no              = trim($_POST['phone_no'] ?? '');
    $address               = trim($_POST['address'] ?? '');
    $age                   = trim($_POST['age'] ?? '');
    $state_of_origin       = trim($_POST['state_of_origin'] ?? '');
    $lga_origin            = trim($_POST['lga_origin'] ?? '');
    $state_of_residence    = trim($_POST['state_of_residence'] ?? '');
    $lga_of_residence      = trim($_POST['lga_of_residence'] ?? '');
    $parent_name           = trim($_POST['parent_name'] ?? '');
    $parent_address        = trim($_POST['parent_address'] ?? '');
    $parent_occupation     = trim($_POST['parent_occupation'] ?? '');
    $religion              = trim($_POST['religion'] ?? '');
    $child_comment         = trim($_POST['child_comment'] ?? '');
    $status                = trim($_POST['status'] ?? 'active');

    if (empty($student) && !empty($id_from_form)) {
        $stmt_re_fetch = $conn->prepare("SELECT * FROM students WHERE id = ?");
        if ($stmt_re_fetch) {
            $stmt_re_fetch->bind_param("i", $id_from_form);
            $stmt_re_fetch->execute();
            $student = $stmt_re_fetch->get_result()->fetch_assoc();
            $stmt_re_fetch->close();
        }
    }

    $max_size = 2 * 1024 * 1024;
    $allowed_img = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_doc = array_merge($allowed_img, ['application/pdf']);

    $passport_photo = $student['passport_photo'] ?? '';
    $birth_certificate = $student['birth_certificate'] ?? '';
    $testimonial = $student['testimonial'] ?? '';

    $upload_errors = []; // Initialize $upload_errors

    function handleFileUpload($file_input_name, $target_sub_dir, $allowed_types, $max_size, $current_path, &$upload_errors) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../../uploads/" . $target_sub_dir . "/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
            $file_original_name = $_FILES[$file_input_name]['name'];
            $file_size = $_FILES[$file_input_name]['size'];
            $file_type = mime_content_type($file_tmp_name);
            if (!in_array($file_type, $allowed_types)) {
                $upload_errors[] = "Invalid " . str_replace('_', ' ', $file_input_name) . " type. Allowed: " . implode(', ', $allowed_types) . ".";
            } elseif ($file_size > $max_size) {
                $upload_errors[] = str_replace('_', ' ', $file_input_name) . " too large. Max " . ($max_size / 1024 / 1024) . "MB.";
            } else {
                $file_extension = pathinfo($file_original_name, PATHINFO_EXTENSION);
                $new_file_name = time() . "_" . uniqid() . "." . $file_extension;
                $new_upload_path = $target_dir . $new_file_name;
                if (move_uploaded_file($file_tmp_name, $new_upload_path)) {
                    if (!empty($current_path) && file_exists($current_path)) {
                        unlink($current_path);
                    }
                    return $new_upload_path;
                } else {
                    $upload_errors[] = "Failed to upload " . str_replace('_', ' ', $file_input_name) . ".";
                }
            }
        }
        return $current_path;
    }

    $passport_photo = handleFileUpload('passport_photo', 'passports', $allowed_img, $max_size, $passport_photo, $upload_errors);
    $birth_certificate = handleFileUpload('birth_certificate', 'birth_certificates', $allowed_doc, $max_size, $birth_certificate, $upload_errors);
    $testimonial = handleFileUpload('testimonial', 'testimonials', $allowed_doc, $max_size, $testimonial, $upload_errors);

    if (empty($upload_errors)) {
        // --- Updating an existing student ---
        $stmt = $conn->prepare("UPDATE students SET
            full_name=?, phone_no=?, email_address=?, status=?, address=?, age=?, state_of_origin=?, lga_origin=?, state_of_residence=?, lga_of_residence=?, parent_name=?, parent_address=?, parent_occupation=?, religion=?, child_comment=?, birth_certificate=?, testimonial=?, passport_photo=?, class_assigned=?, student_id=?
            WHERE id=?");

        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssssssssssssi", // s = string, i = integer
                $full_name, $phone_no, $email_address, $status, $address, $age, $state_of_origin, $lga_origin, $state_of_residence, $lga_of_residence,
                $parent_name, $parent_address, $parent_occupation, $religion, $child_comment, $birth_certificate, $testimonial, $passport_photo, $class_assigned, $student_id_form, $id_from_form
            );

            if ($stmt->execute()) {
                $message .= "<div class='alert alert-success'>Student updated successfully.</div>";
                // Re-fetch student data to display the most current values on the form immediately after update.
                $stmt_re_fetch_after_update = $conn->prepare("SELECT * FROM students WHERE id = ?");
                $stmt_re_fetch_after_update->bind_param("i", $id_from_form);
                $stmt_re_fetch_after_update->execute();
                $student = $stmt_re_fetch_after_update->get_result()->fetch_assoc();
                $stmt_re_fetch_after_update->close();
            } else {
                $message .= "<div class='alert alert-danger'>Error updating student: " . htmlspecialchars($stmt->error) . "</div>";
            }
            $stmt->close(); // Close the update statement.
        } else {
            $message .= "<div class='alert alert-danger'>Database prepare error for update: " . htmlspecialchars($conn->error) . "</div>";
        }
        // Redirect to the same page after POST to prevent form re-submission on refresh
        // and to display the message (passed via GET parameter).
        header("Location: edit_student.php?id=" . $id_from_form . "&msg=" . urlencode(strip_tags($message)));
        exit; // Terminate script execution after redirection.
    } else {
        // If there were file upload errors, display them to the user.
        foreach ($upload_errors as $error) {
            $message .= "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        }
        // Form values will persist because they are retrieved from $_POST in the HTML.
    }
}

// --- Display messages passed via GET parameter after a redirect ---
if (isset($_GET['msg'])) {
    // Decode and display the message. Using strip_tags for security as it was encoded.
    $message = "<div class='alert alert-info'>" . htmlspecialchars(urldecode($_GET['msg'])) . "</div>";
}

// --- Fetch all students for the right-hand table display ---
$all_students_result = $conn->query("SELECT id, full_name, class_assigned, email_address FROM students ORDER BY id DESC");
if ($conn->error) {
    // If there's an error fetching all students, append it to the message.
    $message .= "<div class='alert alert-danger'>Error fetching all students: " . htmlspecialchars($conn->error) . "</div>";
}

// Close the database connection.
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style> /* Your existing styles */ </style>
</head>
<body>
<div class="container py-5">
    <div class="row g-4">

        <div class="col-lg-5">
            <div class="card p-4">
                <h4 class="text-center text-primary mb-4"><i class="bi bi-person-lines-fill me-2"></i>Update Student</h4>
                <?php if (!empty($message)) echo $message; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($student['id'] ?? '') ?>">

                    <div class="mb-2">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? $student['full_name'] ?? '') ?>" class="form-control rounded-md" required>
                    </div>
                    <div class="mb-2">
                        <label for="phone_no" class="form-label">Phone No</label>
                        <input type="text" name="phone_no" id="phone_no" value="<?= htmlspecialchars($_POST['phone_no'] ?? $student['phone_no'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="email_address" class="form-label">Email</label>
                        <input type="email" name="email_address" id="email_address" value="<?= htmlspecialchars($_POST['email_address'] ?? $student['email_address'] ?? '') ?>" class="form-control rounded-md" required>
                    </div>
                    <div class="mb-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select rounded-md">
                            <option value="active" <?= (($_POST['status'] ?? $student['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? $student['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="pending" <?= (($_POST['status'] ?? $student['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" name="address" id="address" value="<?= htmlspecialchars($_POST['address'] ?? $student['address'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" name="age" id="age" value="<?= htmlspecialchars($_POST['age'] ?? $student['age'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="state_of_origin" class="form-label">State of Origin</label>
                        <input type="text" name="state_of_origin" id="state_of_origin" value="<?= htmlspecialchars($_POST['state_of_origin'] ?? $student['state_of_origin'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="lga_origin" class="form-label">LGA of Origin</label>
                        <input type="text" name="lga_origin" id="lga_origin" value="<?= htmlspecialchars($_POST['lga_origin'] ?? $student['lga_origin'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="state_of_residence" class="form-label">State of Residence</label>
                        <input type="text" name="state_of_residence" id="state_of_residence" value="<?= htmlspecialchars($_POST['state_of_residence'] ?? $student['state_of_residence'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="lga_of_residence" class="form-label">LGA of Residence</label>
                        <input type="text" name="lga_of_residence" id="lga_of_residence" value="<?= htmlspecialchars($_POST['lga_of_residence'] ?? $student['lga_of_residence'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="parent_name" class="form-label">Parent Name</label>
                        <input type="text" name="parent_name" id="parent_name" value="<?= htmlspecialchars($_POST['parent_name'] ?? $student['parent_name'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="parent_address" class="form-label">Parent Address</label>
                        <input type="text" name="parent_address" id="parent_address" value="<?= htmlspecialchars($_POST['parent_address'] ?? $student['parent_address'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="parent_occupation" class="form-label">Parent Occupation</label>
                        <input type="text" name="parent_occupation" id="parent_occupation" value="<?= htmlspecialchars($_POST['parent_occupation'] ?? $student['parent_occupation'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="religion" class="form-label">Religion</label>
                        <input type="text" name="religion" id="religion" value="<?= htmlspecialchars($_POST['religion'] ?? $student['religion'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="child_comment" class="form-label">Child Comment</label>
                        <input type="text" name="child_comment" id="child_comment" value="<?= htmlspecialchars($_POST['child_comment'] ?? $student['child_comment'] ?? '') ?>" class="form-control rounded-md">
                    </div>
                    <div class="mb-2">
                        <label for="birth_certificate" class="form-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" id="birth_certificate" class="form-control rounded-md" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (!empty($student['birth_certificate'])): ?>
                            <small class="form-text text-muted">Current: <a href="<?= htmlspecialchars($student['birth_certificate'] ?? '') ?>" target="_blank">View File</a></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label for="testimonial" class="form-label">Testimonial</label>
                        <input type="file" name="testimonial" id="testimonial" class="form-control rounded-md" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (!empty($student['testimonial'])): ?>
                            <small class="form-text text-muted">Current: <a href="<?= htmlspecialchars($student['testimonial'] ?? '') ?>" target="_blank">View File</a></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label for="passport_photo" class="form-label">Passport Photo</label>
                        <input type="file" name="passport_photo" id="passport_photo" class="form-control rounded-md" accept="image/*">
                        <?php if (!empty($student['passport_photo'])): ?>
                            <small class="form-text text-muted">Current: <a href="<?= htmlspecialchars($student['passport_photo'] ?? '') ?>" target="_blank">View Photo</a></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label for="class_assigned" class="form-label">Class Assigned</label>
                        <select name="class_assigned" id="class_assigned" class="form-select rounded-md" required>
                            <option value="">--Select--</option>
                            <?php
                            $classes = ['Basic 1','Basic 2','Basic 3','Basic 4','Basic 5','Basic 6'];
                            foreach ($classes as $cls) {
                                $selected = (($_POST['class_assigned'] ?? $student['class_assigned'] ?? '') === $cls) ? 'selected' : '';
                                echo "<option value='$cls' $selected>$cls</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="student_id_input" class="form-label">Student ID (Internal)</label>
                        <input type="text" name="student_id" id="student_id_input" value="<?= htmlspecialchars($_POST['student_id'] ?? $student['student_id'] ?? '') ?>" class="form-control rounded-md">
                        <small class="form-text text-muted">This updates the `student_id` column in your `students` table.</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3"><i class="bi bi-save me-2"></i>Update Student</button>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle me-2"></i>Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card p-4">
                <h4 class="text-primary mb-3"><i class="bi bi-people-fill me-2"></i>All Students</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_students_result && $all_students_result->num_rows > 0):
                                $sn = 1;
                                while ($row = $all_students_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $sn++ ?></td>
                                        <td><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['class_assigned'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['email_address'] ?? '') ?></td>
                                        <td>
                                            <a href="edit_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil-square"></i> Edit</a>
                                            <a href="delete_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')"><i class="bi bi-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">No students found in the system.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>