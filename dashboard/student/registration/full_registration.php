<?php
session_start();
include "../../../config.php";

// Enable strict error reporting for development (set display_errors to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Display errors on the screen for development
ini_set('log_errors', 1); // Ensure errors are logged
ini_set('error_log', 'error_log.txt'); // Specify error log file

// Ensure student is logged in
$unique_id = $_SESSION['student_unique_id'] ?? null;
if (!$unique_id) {
    header("Location: ../../../login.php");
    exit;
}

// Fetch student data from database
$stmt = $conn->prepare("SELECT address, dob, state_of_origin, lga_origin, state_of_residence, lga_of_residence, sex, tribe, town_of_residence, schools_attended, exam_center, present_class, postal_address FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc() ?? [];
$stmt->close();

// Pre-fetch session data as fallback
$session_data = [
    'address' => $_SESSION['reg_address'] ?? $student_data['address'] ?? '',
    'dob' => $_SESSION['reg_dob'] ?? $student_data['dob'] ?? '',
    'state_of_origin' => $_SESSION['reg_state_of_origin'] ?? $student_data['state_of_origin'] ?? '',
    'lga_origin' => $_SESSION['reg_lga_origin'] ?? $student_data['lga_origin'] ?? '',
    'state_of_residence' => $_SESSION['reg_state_of_residence'] ?? $student_data['state_of_residence'] ?? '',
    'lga_of_residence' => $_SESSION['reg_lga_of_residence'] ?? $student_data['lga_of_residence'] ?? '',
    'sex' => $_SESSION['reg_sex'] ?? $student_data['sex'] ?? '',
    'tribe' => $_SESSION['reg_tribe'] ?? $student_data['tribe'] ?? '',
    'town_of_residence' => $_SESSION['reg_town_of_residence'] ?? $student_data['town_of_residence'] ?? '',
    'schools_attended' => $_SESSION['reg_schools_attended'] ?? $student_data['schools_attended'] ?? '',
    'exam_center' => $_SESSION['reg_exam_center'] ?? $student_data['exam_center'] ?? '',
    'present_class' => $_SESSION['reg_present_class'] ?? $student_data['present_class'] ?? '',
    'postal_address' => $_SESSION['reg_postal_address'] ?? $student_data['postal_address'] ?? ''
];

$message = "";

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $required_fields = ['address', 'dob', 'state_of_origin', 'lga_origin', 'state_of_residence', 'lga_of_residence', 'sex'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "The $field field is required.";
        }
    }

    // Validate sex field
    $valid_sex_options = ['Male', 'Female', 'Other'];
    if (!in_array($_POST['sex'] ?? '', $valid_sex_options)) {
        $errors[] = "Invalid sex selected.";
    }

    // Validate dob format (YYYY-MM-DD)
    $dob = $_POST['dob'] ?? '';
    if ($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $errors[] = "Invalid date of birth format.";
    }

    if (empty($errors)) {
        // Store sanitized data in session
        $_SESSION['reg_address'] = sanitize_input($_POST['address']);
        $_SESSION['reg_dob'] = sanitize_input($_POST['dob']);
        $_SESSION['reg_state_of_origin'] = sanitize_input($_POST['state_of_origin']);
        $_SESSION['reg_lga_origin'] = sanitize_input($_POST['lga_origin']);
        $_SESSION['reg_state_of_residence'] = sanitize_input($_POST['state_of_residence']);
        $_SESSION['reg_lga_of_residence'] = sanitize_input($_POST['lga_of_residence']);
        $_SESSION['reg_sex'] = sanitize_input($_POST['sex']);
        $_SESSION['reg_tribe'] = sanitize_input($_POST['tribe'] ?? '');
        $_SESSION['reg_town_of_residence'] = sanitize_input($_POST['town_of_residence'] ?? '');
        $_SESSION['reg_schools_attended'] = sanitize_input($_POST['schools_attended'] ?? '');
        $_SESSION['reg_exam_center'] = sanitize_input($_POST['exam_center'] ?? '');
        $_SESSION['reg_present_class'] = sanitize_input($_POST['present_class'] ?? '');
        $_SESSION['reg_postal_address'] = sanitize_input($_POST['postal_address'] ?? '');

        header("Location: step2.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Full Registration - Step 1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Full Registration - Step 1</h4>
        <?php if ($message) echo $message; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($session_data['address']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="dob" class="form-label">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control" value="<?= htmlspecialchars($session_data['dob']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="state_of_origin" class="form-label">State of Origin</label>
                    <input type="text" id="state_of_origin" name="state_of_origin" class="form-control" value="<?= htmlspecialchars($session_data['state_of_origin']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="lga_origin" class="form-label">LGA of Origin</label>
                    <input type="text" id="lga_origin" name="lga_origin" class="form-control" value="<?= htmlspecialchars($session_data['lga_origin']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="state_of_residence" class="form-label">State of Residence</label>
                    <input type="text" id="state_of_residence" name="state_of_residence" class="form-control" value="<?= htmlspecialchars($session_data['state_of_residence']) ?>" required>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="lga_of_residence" class="form-label">LGA of Residence</label>
                    <input type="text" id="lga_of_residence" name="lga_of_residence" class="form-control" value="<?= htmlspecialchars($session_data['lga_of_residence']) ?>" required>
                </div>
                <div class="mb-3 col-md-4">
                    <label for="sex" class="form-label">Sex</label>
                    <select id="sex" name="sex" class="form-control" required>
                        <option value="">Select Sex</option>
                        <option value="Male" <?= $session_data['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $session_data['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $session_data['sex'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="mb-3 col-md-4">
                    <label for="tribe" class="form-label">Tribe</label>
                    <input type="text" id="tribe" name="tribe" class="form-control" value="<?= htmlspecialchars($session_data['tribe']) ?>">
                </div>
                <div class="mb-3 col-md-4">
                    <label for="town_of_residence" class="form-label">Town of Residence</label>
                    <input type="text" id="town_of_residence" name="town_of_residence" class="form-control" value="<?= htmlspecialchars($session_data['town_of_residence']) ?>">
                </div>
                <div class="mb-3 col-md-12">
                    <label for="schools_attended" class="form-label">School(s) Attended</label>
                    <textarea id="schools_attended" name="schools_attended" class="form-control"><?= htmlspecialchars($session_data['schools_attended']) ?></textarea>
                </div>
                <div class="mb-3 col-md-6">
                    <label for="exam_center" class="form-label">Preferred Examination Center</label>
                    <input type="text" id="exam_center" name="exam_center" class="form-control" value="<?= htmlspecialchars($session_data['exam_center']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="present_class" class="form-label">Present Class</label>
                    <input type="text" id="present_class" name="present_class" class="form-control" value="<?= htmlspecialchars($session_data['present_class']) ?>">
                </div>
                <div class="mb-3 col-md-12">
                    <label for="postal_address" class="form-label">Postal Address</label>
                    <textarea id="postal_address" name="postal_address" class="form-control"><?= htmlspecialchars($session_data['postal_address']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Next</button>
        </form>
    </div>
</div>
</body>
</html>