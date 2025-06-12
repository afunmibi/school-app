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

// Fetch student data from database
$stmt = $conn->prepare("SELECT parent_name, parent_address, parent_occupation, religion, child_comment, parent_marital_status, parent_email, parent_phone, born_again, born_again_year, church_affiliation, church_position, parent_relationship, lived_together_duration, health_challenges FROM students WHERE unique_id = ?");
$stmt->bind_param("s", $unique_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc() ?? [];
$stmt->close();

// Pre-fetch session data as fallback
$session_data = [
    'parent_name' => $_SESSION['reg_parent_name'] ?? $student_data['parent_name'] ?? '',
    'parent_address' => $_SESSION['reg_parent_address'] ?? $student_data['parent_address'] ?? '',
    'parent_occupation' => $_SESSION['reg_parent_occupation'] ?? $student_data['parent_occupation'] ?? '',
    'religion' => $_SESSION['reg_religion'] ?? $student_data['religion'] ?? '',
    'child_comment' => $_SESSION['reg_child_comment'] ?? $student_data['child_comment'] ?? '',
    'parent_marital_status' => $_SESSION['reg_parent_marital_status'] ?? $student_data['parent_marital_status'] ?? '',
    'parent_email' => $_SESSION['reg_parent_email'] ?? $student_data['parent_email'] ?? '',
    'parent_phone' => $_SESSION['reg_parent_phone'] ?? $student_data['parent_phone'] ?? '',
    'born_again' => $_SESSION['reg_born_again'] ?? $student_data['born_again'] ?? '',
    'born_again_year' => $_SESSION['reg_born_again_year'] ?? $student_data['born_again_year'] ?? '',
    'church_affiliation' => $_SESSION['reg_church_affiliation'] ?? $student_data['church_affiliation'] ?? '',
    'church_position' => $_SESSION['reg_church_position'] ?? $student_data['church_position'] ?? '',
    'parent_relationship' => $_SESSION['reg_parent_relationship'] ?? $student_data['parent_relationship'] ?? '',
    'lived_together_duration' => $_SESSION['reg_lived_together_duration'] ?? $student_data['lived_together_duration'] ?? '',
    'health_challenges' => $_SESSION['reg_health_challenges'] ?? $student_data['health_challenges'] ?? ''
];

$message = "";

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    $required_fields = ['parent_name', 'parent_address', 'parent_occupation', 'religion'];
    $errors = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "The $field field is required.";
        }
    }

    // Validate email format
    $parent_email = $_POST['parent_email'] ?? '';
    if ($parent_email && !filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid parent email format.";
    }

    // Validate born_again
    $valid_born_again_options = ['Yes', 'No', ''];
    $born_again = $_POST['born_again'] ?? '';
    if (!in_array($born_again, $valid_born_again_options)) {
        $errors[] = "Invalid born again selection.";
    }

    // Validate born_again_year (if provided, should be a valid year)
    $born_again_year = $_POST['born_again_year'] ?? '';
    if ($born_again_year && !preg_match('/^\d{4}$/', $born_again_year)) {
        $errors[] = "Invalid born again year format (e.g., 2023).";
    }

    // Validate lived_together_duration (if provided, should be numeric)
    $lived_together_duration = $_POST['lived_together_duration'] ?? '';
    if ($lived_together_duration && !is_numeric($lived_together_duration)) {
        $errors[] = "Duration lived together must be a number.";
    }

    if (empty($errors)) {
        // Store sanitized data in session
        $_SESSION['reg_parent_name'] = sanitize_input($_POST['parent_name']);
        $_SESSION['reg_parent_address'] = sanitize_input($_POST['parent_address']);
        $_SESSION['reg_parent_occupation'] = sanitize_input($_POST['parent_occupation']);
        $_SESSION['reg_religion'] = sanitize_input($_POST['religion']);
        $_SESSION['reg_child_comment'] = sanitize_input($_POST['child_comment'] ?? '');
        $_SESSION['reg_parent_marital_status'] = sanitize_input($_POST['parent_marital_status'] ?? '');
        $_SESSION['reg_parent_email'] = sanitize_input($_POST['parent_email'] ?? '');
        $_SESSION['reg_parent_phone'] = sanitize_input($_POST['parent_phone'] ?? '');
        $_SESSION['reg_born_again'] = sanitize_input($_POST['born_again'] ?? '');
        $_SESSION['reg_born_again_year'] = sanitize_input($_POST['born_again_year'] ?? '');
        $_SESSION['reg_church_affiliation'] = sanitize_input($_POST['church_affiliation'] ?? '');
        $_SESSION['reg_church_position'] = sanitize_input($_POST['church_position'] ?? '');
        $_SESSION['reg_parent_relationship'] = sanitize_input($_POST['parent_relationship'] ?? '');
        $_SESSION['reg_lived_together_duration'] = sanitize_input($_POST['lived_together_duration'] ?? '');
        $_SESSION['reg_health_challenges'] = sanitize_input($_POST['health_challenges'] ?? '');

        header("Location: step3.php");
        exit;
    } else {
        $message = "<div class='alert alert-danger'>" . implode("<br>", array_map('htmlspecialchars', $errors)) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Full Registration - Step 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Full Registration - Step 2</h4>
        <?php if ($message) echo $message; ?>
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
                <div class="mb-3 col-md-6">
                    <label for="child_comment" class="form-label">Child Comment</label>
                    <input type="text" id="child_comment" name="child_comment" class="form-control" value="<?= htmlspecialchars($session_data['child_comment']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_marital_status" class="form-label">Parent Marital Status</label>
                    <input type="text" id="parent_marital_status" name="parent_marital_status" class="form-control" value="<?= htmlspecialchars($session_data['parent_marital_status']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_email" class="form-label">Parent Email</label>
                    <input type="email" id="parent_email" name="parent_email" class="form-control" value="<?= htmlspecialchars($session_data['parent_email']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_phone" class="form-label">Parent Phone Number</label>
                    <input type="text" id="parent_phone" name="parent_phone" class="form-control" value="<?= htmlspecialchars($session_data['parent_phone']) ?>">
                </div>
                <div class="mb-3 col-md-4">
                    <label for="born_again" class="form-label">Are you Born Again?</label>
                    <select id="born_again" name="born_again" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes" <?= $session_data['born_again'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                        <option value="No" <?= $session_data['born_again'] === 'No' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                <div class="mb-3 col-md-4">
                    <label for="born_again_year" class="form-label">If Yes, When?</label>
                    <input type="text" id="born_again_year" name="born_again_year" class="form-control" value="<?= htmlspecialchars($session_data['born_again_year']) ?>">
                </div>
                <div class="mb-3 col-md-4">
                    <label for="church_affiliation" class="form-label">Church/Ministry Affiliation</label>
                    <input type="text" id="church_affiliation" name="church_affiliation" class="form-control" value="<?= htmlspecialchars($session_data['church_affiliation']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="church_position" class="form-label">Present Post/Position (in Church)</label>
                    <input type="text" id="church_position" name="church_position" class="form-control" value="<?= htmlspecialchars($session_data['church_position']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="parent_relationship" class="form-label">Relationship with the Candidate</label>
                    <input type="text" id="parent_relationship" name="parent_relationship" class="form-control" value="<?= htmlspecialchars($session_data['parent_relationship']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="lived_together_duration" class="form-label">Duration Lived Together (Years)</label>
                    <input type="text" id="lived_together_duration" name="lived_together_duration" class="form-control" value="<?= htmlspecialchars($session_data['lived_together_duration']) ?>">
                </div>
                <div class="mb-3 col-md-6">
                    <label for="health_challenges" class="form-label">Physical Handicap, Health Challenge or Disability</label>
                    <textarea id="health_challenges" name="health_challenges" class="form-control"><?= htmlspecialchars($session_data['health_challenges']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Next</button>
        </form>
    </div>
</div>
</body>
</html>