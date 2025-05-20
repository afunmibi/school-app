<?php
session_start();
include "../config.php";

// Only allow admin for approval actions
if (
    (isset($_GET['action']) && $_GET['action'] === 'approve') ||
    (isset($_GET['action']) && $_GET['action'] === 'reject')
) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../../index.php");
        exit;
    }
}

$message = "";
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// 1. Handle Pre-registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['full_name'], $_POST['phone_no'], $_POST['email_address'])) {
        $message = "Please fill out all required fields.";
    } else {
        $name = trim($_POST['full_name']);
        $phone = trim($_POST['phone_no']);
        $email = trim($_POST['email_address']);

        if (empty($name) || empty($phone) || empty($email)) {
            $message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
        } else {
            // Check for duplicate email
            $check = $conn->prepare("SELECT id FROM pre_registration1 WHERE email_address = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $message = "Email already registered.";
            } else {
                // Generate unique ID and random password
                $unique_id = strtoupper(substr($name, 0, 3)) . rand(1000, 9999);
                $plain_password = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"), 0, 8);

                // Insert into pre_registration1
                $stmt = $conn->prepare("INSERT INTO pre_registration1 (full_name, phone_no, email_address, unique_id, raw_password, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("sssss", $name, $phone, $email, $unique_id, $plain_password);

                if ($stmt->execute()) {
                    $message = "Registration successful!<br>
                        <strong>Your Unique ID:</strong> $unique_id<br>
                        <strong>Your Temporary Password:</strong> $plain_password<br>
                        Please keep them safe. You will be able to log in once approved by the admin.";
                } else {
                    $message = "Error during registration: " . $stmt->error;
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

// 2. Handle Admin Approval
if ($action == 'approve' && $id) {
    // Update status to approved
    $stmt = $conn->prepare("UPDATE pre_registration1 SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Fetch student data
    $stmt = $conn->prepare("SELECT * FROM pre_registration1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        $hashed_password = password_hash($student['raw_password'], PASSWORD_DEFAULT);

        // Update or Insert into student_login
        $check_login = $conn->prepare("SELECT id FROM student_login WHERE pre_reg_id = ?");
        $check_login->bind_param("i", $id);
        $check_login->execute();
        $login_result = $check_login->get_result();

        if ($login_result->num_rows > 0) {
            // Update existing login
            $update_login = $conn->prepare("UPDATE student_login SET unique_id = ?, email = ?, password = ? WHERE pre_reg_id = ?");
            $update_login->bind_param("sssi", $student['unique_id'], $student['email_address'], $hashed_password, $id);
            $update_login->execute();
            $update_login->close();
        } else {
            // Insert new login
            $insert_login = $conn->prepare("INSERT INTO student_login (pre_reg_id, unique_id, email, password) VALUES (?, ?, ?, ?)");
            $insert_login->bind_param("isss", $id, $student['unique_id'], $student['email_address'], $hashed_password);
            $insert_login->execute();
            $insert_login->close();
        }
        $check_login->close();

        // Prevent duplicate in students table
        $check_student = $conn->prepare("SELECT id FROM students WHERE pre_reg_id = ?");
        $check_student->bind_param("i", $id);
        $check_student->execute();
        $student_result = $check_student->get_result();

        if ($student_result->num_rows == 0) {
            // Insert into students table
            $stmt_students = $conn->prepare("INSERT INTO students (pre_reg_id, full_name, phone_no, email_address, unique_id, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt_students->bind_param("issss", $id, $student['full_name'], $student['phone_no'], $student['email_address'], $student['unique_id']);
            $stmt_students->execute();
            $stmt_students->close();
        }
        $check_student->close();

        // Remove raw password for security
        $stmt = $conn->prepare("UPDATE pre_registration1 SET raw_password = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: approve_students.php");
    exit;
}

$conn->close();
?>