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
    // ... (rest of your form processing code remains the same) ...
}
?>