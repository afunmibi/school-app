<?php
require_once '../../config.php';

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = ""; // Message for form submission feedback

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $full_name          = trim($_POST['full_name'] ?? '');
    $phone_no           = trim($_POST['phone_no'] ?? '');
    $email_address      = trim($_POST['email_address'] ?? '');
    $status             = trim($_POST['status'] ?? 'active');
    $address            = trim($_POST['address'] ?? '');
    $age                = trim($_POST['age'] ?? '');
    $state_of_origin    = trim($_POST['state_of_origin'] ?? '');
    $lga_origin         = trim($_POST['lga_origin'] ?? '');
    $state_of_residence = trim($_POST['state_of_residence'] ?? '');
    $lga_of_residence   = trim($_POST['lga_of_residence'] ?? '');
    $parent_name        = trim($_POST['parent_name'] ?? '');
    $parent_address     = trim($_POST['parent_address'] ?? '');
    $parent_occupation  = trim($_POST['parent_occupation'] ?? '');
    $religion           = trim($_POST['religion'] ?? '');
    $child_comment      = trim($_POST['child_comment'] ?? '');
    $birth_certificate  = trim($_POST['birth_certificate'] ?? '');
    $testimonial        = trim($_POST['testimonial'] ?? '');
    $class_assigned     = trim($_POST['class_assigned'] ?? '');
    $student_id         = trim($_POST['student_id'] ?? '');

    // File upload for passport photo
    $passport_photo = '';
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === 0) {
        $target_dir = "../../uploads/passports/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_type = mime_content_type($_FILES['passport_photo']['tmp_name']);
        $file_size = $_FILES['passport_photo']['size'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_type, $allowed_types)) {
            $message = "<div class='alert alert-danger'>Invalid passport photo type. Only JPG, PNG, GIF allowed.</div>";
        } elseif ($file_size > $max_size) {
            $message = "<div class='alert alert-danger'>Passport photo too large. Max 2MB allowed.</div>";
        } else {
            $filename = uniqid() . '_' . basename($_FILES['passport_photo']['name']);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $target_file)) {
                $passport_photo = $filename;
            }
        }
    }

    // Only insert if no file upload error
    if (empty($message)) {
        $stmt = $conn->prepare("INSERT INTO students (
            full_name, phone_no, email_address, status, address, age, state_of_origin, lga_origin, state_of_residence, lga_of_residence,
            parent_name, parent_address, parent_occupation, religion, child_comment, birth_certificate, testimonial, passport_photo, class_assigned, student_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssssssssssssssss",
            $full_name, $phone_no, $email_address, $status, $address, $age, $state_of_origin, $lga_origin, $state_of_residence, $lga_of_residence,
            $parent_name, $parent_address, $parent_occupation, $religion, $child_comment, $birth_certificate, $testimonial, $passport_photo, $class_assigned, $student_id
        );

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Student added successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding student: " . htmlspecialchars($stmt->error) . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body { background: #f0f4ff; }
        .card { border-radius: 1rem; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <!-- Form Section -->
        <div class="col-lg-6">
            <div class="card p-4">
                <h4 class="text-primary mb-3">Add New Student</h4>
                <?php if (!empty($message)) echo $message; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-2"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="mb-2"><label>Phone No</label><input type="text" name="phone_no" class="form-control"></div>
                    <div class="mb-2"><label>Email</label><input type="email" name="email_address" class="form-control" required></div>
                    <div class="mb-2"><label>Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-2"><label>Address</label><input type="text" name="address" class="form-control"></div>
                    <div class="mb-2"><label>Age</label><input type="number" name="age" class="form-control"></div>
                    <div class="mb-2"><label>State of Origin</label><input type="text" name="state_of_origin" class="form-control"></div>
                    <div class="mb-2"><label>LGA of Origin</label><input type="text" name="lga_origin" class="form-control"></div>
                    <div class="mb-2"><label>State of Residence</label><input type="text" name="state_of_residence" class="form-control"></div>
                    <div class="mb-2"><label>LGA of Residence</label><input type="text" name="lga_of_residence" class="form-control"></div>
                    <div class="mb-2"><label>Parent Name</label><input type="text" name="parent_name" class="form-control"></div>
                    <div class="mb-2"><label>Parent Address</label><input type="text" name="parent_address" class="form-control"></div>
                    <div class="mb-2"><label>Parent Occupation</label><input type="text" name="parent_occupation" class="form-control"></div>
                    <div class="mb-2"><label>Religion</label><input type="text" name="religion" class="form-control"></div>
                    <div class="mb-2"><label>Child Comment</label><input type="text" name="child_comment" class="form-control"></div>
                    <div class="mb-2"><label>Birth Certificate</label><input type="text" name="birth_certificate" class="form-control"></div>
                    <div class="mb-2"><label>Testimonial</label><input type="text" name="testimonial" class="form-control"></div>
                    <div class="mb-2"><label>Passport Photo</label><input type="file" name="passport_photo" class="form-control" accept="image/*"></div>
                    <div class="mb-2"><label>Class Assigned</label>
                        <select name="class_assigned" class="form-select">
                            <option value="">Select Class</option>
                            <?php
                            $classes = ['Basic 1','Basic 2','Basic 3','Basic 4','Basic 5','Basic 6'];
                            foreach ($classes as $cls) {
                                echo "<option value='$cls'>$cls</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-2"><label>Student ID</label><input type="text" name="student_id" class="form-control"></div>
                    <button class="btn btn-success w-100" name="submit">Add Student</button>
                </form>
            </div>
            <div class="text-center mt-3">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <!-- Students Table -->
        <div class="col-lg-6">
            <div class="card p-4">
                <h4 class="text-primary mb-3">All Students</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Class</th><th>Email</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $sn = 1;
                        $res = $conn->query("SELECT * FROM students ORDER BY id DESC");
                        while ($row = $res->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['class_assigned']) ?></td>
                            <td><?= htmlspecialchars($row['email_address']) ?></td>
                            <td>
                                <a href="edit_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete student?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>