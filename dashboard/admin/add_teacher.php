<?php
// filepath: c:\xampp\htdocs\PHP-Projects-Here\school-app\dashboard\admin\add_teacher.php
session_start();
include "../../config.php";

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = password_hash(trim($_POST['password'] ?? ''), PASSWORD_DEFAULT);
    $role     = 'teacher';
    $class_assigned = trim($_POST['class_assigned'] ?? '');
    $assigned_subject = trim($_POST['assigned_subject'] ?? '');

    // New fields for teacher_profile
    $qualification = trim($_POST['qualification'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $passport_photo = '';

    // Handle passport photo upload
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "../../uploads/teachers/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION);
        $passport_photo = uniqid('teacher_', true) . '.' . $ext;
        move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_dir . $passport_photo);
    }

    // Check if username or email already exists
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt_check->bind_param("ss", $email, $username);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email or Username already exists. <a href='admin_dashboard.php' class='alert-link'>Go back</a></div>";
    } else {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role, class_assigned) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $username, $password, $role, $class_assigned);
        if ($stmt->execute()) {
            $teacher_id = $stmt->insert_id;

            // Insert into teachers table
            $stmt_teacher = $conn->prepare("INSERT INTO teachers (teacher_id, full_name, email, password, class_assigned) VALUES (?, ?, ?, ?, ?)");
            $stmt_teacher->bind_param("issss", $teacher_id, $name, $email, $password, $class_assigned);
            $stmt_teacher->execute();
            $stmt_teacher->close();

            // Insert into teacher_classes table
            $stmt2 = $conn->prepare("INSERT INTO teacher_classes (teacher_id, full_name, class_assigned) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $teacher_id, $name, $class_assigned);
            $stmt2->execute();
            $stmt2->close();

            // Insert into teacher_profile table
            $stmt_profile = $conn->prepare("INSERT INTO teacher_profile (teacher_id, qualification, phone_number, passport_photo, class_assigned) VALUES (?, ?, ?, ?, ?)");
            $stmt_profile->bind_param("issss", $teacher_id, $qualification, $phone_number, $passport_photo, $class_assigned);
            $stmt_profile->execute();
            $stmt_profile->close();

            $message = "<div class='alert alert-success'>Teacher added successfully. <a href='admin_dashboard.php' class='alert-link'>Go back</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding teacher.</div>";
        }
        $stmt->close();
    }
    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        .container {
            margin-top: 50px;
            max-width: 1100px;
        }
        .form-col {
            max-width: 370px;
            margin-left: auto;
            margin-right: auto;
        }
        .alert {
            margin-bottom: 20px;
        }
        h2 {
            text-align: left;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 1px;
        }
        .table-heading {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e293b;
        }
        .form-control {
            margin-bottom: 10px;
        }   
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
        }           
        .btn-primary:hover {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
        }
        .btn-primary:focus {
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.5);
        }   
        .btn-primary:active {
            background-color: #1e40af;
            border-color: #1e3a8a;
        }
        .btn-primary:disabled {
            background-color: #93c5fd;
            border-color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Form Column -->
            <div class="col-lg-4 form-col mb-4">
                <h2>Add Teacher</h2>
                <?php if (!empty($message)) echo $message; ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" required class="form-control" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" required class="form-control" placeholder="Enter email address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" required class="form-control" placeholder="Choose a username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" required class="form-control" placeholder="Create a password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" required class="form-control" placeholder="e.g. B.Ed, M.Sc">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" required class="form-control" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Passport Photo</label>
                        <input type="file" name="passport_photo" accept="image/*" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Class</label>
                        <select name="class_assigned" required class="form-select">
                            <option value="">-- Select Class --</option>
                            <option value="Basic 1">Basic 1</option>
                            <option value="Basic 2">Basic 2</option>
                            <option value="Basic 3">Basic 3</option>
                            <option value="Basic 4">Basic 4</option>
                            <option value="Basic 5">Basic 5</option>
                            <option value="Basic 6">Basic 6</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Subject</label>
                        <input type="text" name="assigned_subject" required class="form-control" placeholder="e.g. Mathematics">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Teacher</button>
                </form>
            </div>
            <!-- Table Column -->
            <div class="col-lg-8">
                <div class="table-heading">All Teachers</div>
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Class Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sn = 1;
                        $teachers = $conn->query("SELECT * FROM users WHERE role='teacher' ORDER BY id DESC");
                        while ($row = $teachers->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                            <td>
                                <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Update</a>
                                <a href="delete_teacher.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                                
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>