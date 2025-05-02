<?php
session_start();
include "../config.php";

// Redirect if not logged in as student
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

// Fetch pre-registration data using student_id (assumed to be a string)
$query = "
    SELECT p.* 
    FROM pre_registration1 p
    JOIN student_login s ON p.id = s.pre_reg_id
    WHERE s.student_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id); // "s" for string
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

if (!$student_data) {
    echo "Student pre-registration data not found.";
    exit;
}

// Handle Step 2 form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_step2'])) {
    $address = $_POST['address'];
    $age = $_POST['age'];
    $state_of_origin = $_POST['state_of_origin'];
    $lga_of_origin = $_POST['lga_of_origin'];
    $state_of_residence = $_POST['state_of_residence'];
    $lga_of_residence = $_POST['lga_of_residence'];

    // Handle passport upload
    if (isset($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
        $passport_name = basename($_FILES['passport']['name']);
        $passport_ext = strtolower(pathinfo($passport_name, PATHINFO_EXTENSION));

        // Optional: validate file type
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($passport_ext, $allowed_types)) {
            echo "Invalid file type. Only JPG, JPEG, and PNG allowed.";
            exit;
        }

        // Ensure uploads directory exists
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $passport_path = $upload_dir . uniqid("passport_") . '.' . $passport_ext;
        move_uploaded_file($_FILES['passport']['tmp_name'], $passport_path);

        // Update pre_registration table
        $update = $conn->prepare("UPDATE pre_registration1 SET address = ?, age = ?, state_of_origin = ?, lga_of_origin = ?, state_of_residence = ?, lga_of_residence = ?, passport = ? WHERE id = ?");
        $update->bind_param("sssssssi", $address, $age, $state_of_origin, $lga_of_origin, $state_of_residence, $lga_of_residence, $passport_path, $student_data['id']);
        $update->execute();

        header("Location: step3.php");
        exit;
    } else {
        echo "Passport upload failed. Please try again.";
    }
}
?>
