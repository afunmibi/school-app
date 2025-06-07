<?php
session_start();
include "../../../config.php";
require '../../../vendor/autoload.php'; // Include Composer autoloader for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone_no'] ?? '');
    $email = trim($_POST['email_address'] ?? '');

    if (empty($full_name) || empty($phone) || empty($email)) {
        $message = "<div class='alert alert-danger'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>Invalid email format.</div>";
    } else {
        // Check if email already exists in either table
        $exists = false;

        $check1 = $conn->prepare("SELECT id FROM pre_registration1 WHERE email_address = ?");
        $check1->bind_param("s", $email);
        $check1->execute();
        $check1->store_result();
        $exists = $check1->num_rows > 0;
        $check1->close();

        if (!$exists) {
            $check2 = $conn->prepare("SELECT id FROM student_login WHERE email_address = ?");
            $check2->bind_param("s", $email);
            $check2->execute();
            $check2->store_result();
            $exists = $check2->num_rows > 0;
            $check2->close();
        }

        if ($exists) {
            $message = "<div class='alert alert-danger'>Email already registered.</div>";
        } else {
            // Generate credentials
            $unique_id = strtoupper(substr(preg_replace('/\s+/', '', $full_name), 0, 3)) . rand(1000, 9999);
            $registration_id = 'REG' . rand(10000, 99999);
            $plain_password = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"), 0, 8);
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
            $status = 'Pending';
            $role = 'student';
            $student_id = 'SID' . rand(1000, 99999);
            $payment_status = 'Pending';
            $payment_amount = 1000.00; // Your payment amount

            // Insert into pre_registration1
            $stmt = $conn->prepare("INSERT INTO pre_registration1 (registration_id, full_name, phone_no, email_address, unique_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $registration_id, $full_name, $phone, $email, $unique_id, $status);

            if ($stmt->execute()) {
                $stmt->close();

                // Insert into student_login
                $login_stmt = $conn->prepare("INSERT INTO student_login (unique_id, full_name, email_address, phone_no, password, temporary_password) VALUES (?, ?, ?, ?, ?, ?)");
                $login_stmt->bind_param("ssssss", $unique_id, $full_name, $email, $phone, $hashed_password, $plain_password);
                if ($login_stmt->execute()) {
                    $login_stmt->close();

                    // Insert into students table
                    $student_stmt = $conn->prepare("INSERT INTO students (registration_id, full_name, phone_no, email_address, unique_id, status, student_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $student_stmt->bind_param("sssssss", $registration_id, $full_name, $phone, $email, $unique_id, $status, $student_id);
                    $student_stmt->execute();
                    $student_stmt->close();

                    // Insert into student_payments table with pending status
                    $payment_stmt = $conn->prepare("INSERT INTO student_payments (registration_id, unique_id, payment_amount, payment_status) VALUES (?, ?, ?, ?)");
                    $payment_stmt->bind_param("ssds", $registration_id, $unique_id, $payment_amount, $payment_status);
                    $payment_stmt->execute();
                    $payment_stmt->close();

                    $mail = new PHPMailer(true);

                    try {
                        //Server settings
                        $mail->SMTPDebug = SMTP::DEBUG_OFF;
                        $mail->isSMTP();
                        $mail->Host       = 'your_smtp_host'; // Replace with your SMTP host
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'your_smtp_username'; // Replace with your SMTP username
                        $mail->Password   = 'your_smtp_password'; // Replace with your SMTP password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        //Recipients
                        $mail->setFrom('your_school_email@example.com', 'Your School Name'); // Replace with your school email
                        $mail->addAddress($email, $full_name);

                        //Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Student Pre-Registration Details';
                        $mail->Body    = "Dear " . htmlspecialchars($full_name) . ",<br><br>" .
                                         "Your pre-registration was successful!<br><br>" .
                                         "<strong>Registration ID:</strong> " . $registration_id . "<br>" .
                                         "<strong>Student ID:</strong> " . $student_id . "<br>" .
                                         "<strong>Unique ID:</strong> " . $unique_id . "<br>" .
                                         "<strong>Temporary Password:</strong> " . $plain_password . "<br>" .
                                         "<strong>Email:</strong> " . htmlspecialchars($email) . "<br><br>" .
                                         "Kindly pay the sum of <strong>N" . number_format($payment_amount, 2) . "</strong> to <strong>GTBank Account: 0000000000, Felix Adewale</strong>.<br>" .
                                         "Return after 10 minutes to complete your registration.<br><br>" .
                                         "Please keep these credentials safe.<br><br>" .
                                         "Sincerely,<br>Your School Name";

                        $mail->send();
                        $message = "<div class='alert alert-success'>✅ <strong>Registration successful!</strong><br>An email with your registration details and temporary password has been sent to " . htmlspecialchars($email) . ".<br><br>" .
                                   "<strong>Registration ID:</strong> $registration_id<br>" .
                                   "<strong>Student ID:</strong> $student_id<br>" .
                                   "<strong>Unique ID:</strong> $unique_id<br>" .
                                   "<strong>Temporary Password:</strong> $plain_password<br>" .
                                   "⚠️ Kindly pay the sum of <strong>N" . number_format($payment_amount, 2) . "</strong> to <strong>GTBank Account: 0000000000, Felix Adewale</strong>.<br>" .
                                   "Return after 10 minutes to complete your registration.</div>";
                    } catch (Exception $e) {
                        $message = "<div class='alert alert-warning'>⚠️ Registration successful, but failed to send confirmation email. Please ensure your email address is correct. Error: " . $mail->ErrorInfo . "</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger'>❌ Failed to insert into student_login: " . $login_stmt->error . "</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>❌ Failed to save pre-registration: " . $stmt->error . "</div>";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Pre-Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="col-md-6 offset-md-3 p-4 shadow rounded bg-white">
        <h4 class="text-primary mb-4 text-center">Student Pre-Registration</h4>
        <?php if ($message) echo "<div class='alert alert-info'>" . $message . "</div>"; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_no" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email_address" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Submit Pre-Registration</button>
        </form>
    </div>
</div>
</body>
</html>