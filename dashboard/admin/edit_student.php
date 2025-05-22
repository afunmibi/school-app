<?php
require_once "../../config.php"; // Database connection

// Initialize variables
$id = $full_name = $phone_no = $email_address = $status = $address = $age = $state_of_origin = $lga_origin = $state_of_residence = $lga_of_residence = "";
$parent_name = $parent_address = $parent_occupation = $religion = $child_comment = "";
$birth_certificate = $testimonial = $passport_photo = "";
$class_assigned = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to manage students if no valid ID is provided
    header("Location: manage_students.php");
    exit;
}
$message = "";
$student = null;

// Fetch student data
$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    // Redirect if student ID is not found
    header("Location: manage_students.php?msg=Student+not+found");
    exit;
}

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

    // Handle passport photo upload
    $passport_photo = $student['passport_photo'];
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === 0) {
        $target_dir = "../../uploads/passports/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = uniqid() . '_' . basename($_FILES['passport_photo']['name']);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $target_file)) {
            $passport_photo = $filename;
        }
    }

    // Update student
    $stmt = $conn->prepare("UPDATE students SET
        full_name=?, phone_no=?, email_address=?, status=?, address=?, age=?, state_of_origin=?, lga_origin=?, state_of_residence=?, lga_of_residence=?,
        parent_name=?, parent_address=?, parent_occupation=?, religion=?, child_comment=?, birth_certificate=?, testimonial=?, passport_photo=?, class_assigned=?, student_id=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "ssssssssssssssssssssi",
        $full_name, $phone_no, $email_address, $status, $address, $age, $state_of_origin, $lga_origin, $state_of_residence, $lga_of_residence,
        $parent_name, $parent_address, $parent_occupation, $religion, $child_comment, $birth_certificate, $testimonial, $passport_photo, $class_assigned, $student_id, $id
    );
    // Execute the statement

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Student updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error updating student: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
    // Refresh student data
    header("Location: edit_student.php?id=" . $id);
    exit; // Ensure script stops after redirect
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Student</title>
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
        <h4 class="text-primary mb-3">Edit Student</h4>
        <?php if (!empty($message)) echo $message; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $student['id'] ?? '' ?>">
            <div class="mb-2"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" class="form-control" required></div>
            <div class="mb-2"><label>Phone No</label><input type="text" name="phone_no" value="<?= htmlspecialchars($student['phone_no'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Email</label><input type="email" name="email_address" value="<?= htmlspecialchars($student['email_address'] ?? '') ?>" class="form-control" required></div>
            <div class="mb-2"><label>Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= ($student['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($student['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="mb-2"><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($student['address'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Age</label><input type="number" name="age" value="<?= htmlspecialchars($student['age'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>State of Origin</label><input type="text" name="state_of_origin" value="<?= htmlspecialchars($student['state_of_origin'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>LGA of Origin</label><input type="text" name="lga_origin" value="<?= htmlspecialchars($student['lga_origin'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>State of Residence</label><input type="text" name="state_of_residence" value="<?= htmlspecialchars($student['state_of_residence'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>LGA of Residence</label><input type="text" name="lga_of_residence" value="<?= htmlspecialchars($student['lga_of_residence'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Parent Name</label><input type="text" name="parent_name" value="<?= htmlspecialchars($student['parent_name'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Parent Address</label><input type="text" name="parent_address" value="<?= htmlspecialchars($student['parent_address'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Parent Occupation</label><input type="text" name="parent_occupation" value="<?= htmlspecialchars($student['parent_occupation'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Religion</label><input type="text" name="religion" value="<?= htmlspecialchars($student['religion'] ?? '') ?>" class="form-control"></div>
            <div class="mb-2"><label>Child Comment</label><input type="text" name="child_comment" value="<?= htmlspecialchars($student['child_comment'] ?? '') ?>" class="form-control"></div>
            
            <div class="mb-2"><label>Birth Certificate</label>
                <input type="file" name="birth_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($student['birth_certificate'])): ?>
                    <div class="mt-1">
                        <a href="../../uploads/birth_certificates/<?= htmlspecialchars($student['birth_certificate']) ?>" target="_blank">View Current</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-2"><label>Testimonial</label>
                <input type="file" name="testimonial" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <?php if (!empty($student['testimonial'])): ?>
                    <div class="mt-1">
                        <a href="../../uploads/testimonials/<?= htmlspecialchars($student['testimonial']) ?>" target="_blank">View Current</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-2"><label>Passport Photo</label>
                <input type="file" name="passport_photo" class="form-control" accept="image/*">
                <?php if (!empty($student['passport_photo'])): ?>
                    <img src="../../uploads/passports/<?= htmlspecialchars($student['passport_photo']) ?>" alt="Passport" style="max-width:80px;max-height:80px;margin-top:5px;">
                <?php endif; ?>
            </div>
            <div class="mb-2"><label>Class Assigned</label>
                <select name="class_assigned" class="form-select">
                    <option value="">Select Class</option>
                    <?php
                    $classes = ['Basic 1','Basic 2','Basic 3','Basic 4','Basic 5','Basic 6'];
                    foreach ($classes as $cls) {
                        $selected = ($student['class_assigned'] ?? '') === $cls ? 'selected' : '';
                        echo "<option value='$cls' $selected>$cls</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-2"><label>Student ID</label><input type="text" name="student_id" value="<?= htmlspecialchars($student['student_id'] ?? '') ?>" class="form-control"></div>
            <button class="btn btn-success w-100" name="submit">Update Student</button>
        </form>
    </div>
    <div class="text-center mt-3">
        <a href="manage_students.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Manage Students</a>
    </div>
</div>    </div>
</div>
</body>
</html>