<?php
session_start();
include "../../config.php";

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";
$student = null;

// Fetch student by ID if editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $id                 = $_POST['id'];
    $full_name          = trim($_POST['name'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $class              = trim($_POST['class'] ?? '');
    $address            = trim($_POST['address'] ?? '');
    $age                = trim($_POST['age'] ?? '');
    $state_of_origin    = trim($_POST['state_of_origin'] ?? '');
    $lga_origin         = trim($_POST['lga_origin'] ?? '');
    $state_of_residence = trim($_POST['state_of_residence'] ?? '');
    $lga_residence      = trim($_POST['lga_residence'] ?? '');
    $religion           = trim($_POST['religion'] ?? '');
    $phone_no           = trim($_POST['phone_no'] ?? '');
    $status             = trim($_POST['status'] ?? 'active');
    $date_registered    = date("Y-m-d");

    // Handle file upload
    $passport_photo = $student['passport_photo'] ?? '';
    if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === 0) {
        $target_dir = "../../uploads/passports/";
        $photo_name = time() . "_" . basename($_FILES['passport_photo']['name']);
        $upload_path = $target_dir . $photo_name;

        if (move_uploaded_file($_FILES['passport_photo']['tmp_name'], $upload_path)) {
            $passport_photo = $upload_path;
        }
    }

    // Update student
    $stmt = $conn->prepare("UPDATE students SET 
        full_name = ?, address = ?, age = ?, state_of_origin = ?, lga_origin = ?, 
        state_of_residence = ?, lga_residence = ?, passport_photo = ?, religion = ?, 
        class = ?, date_registered = ?, phone_no = ?, email_address = ?, status = ? 
        WHERE id = ?");

    $stmt->bind_param("ssssssssssssssi", 
        $full_name, $address, $age, $state_of_origin, $lga_origin, 
        $state_of_residence, $lga_residence, $passport_photo, $religion,
        $class, $date_registered, $phone_no, $email, $status, $id
    );

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Student updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error updating student.</div>";
    }

    $stmt->close();
    // Refresh student data
    header("Location: edit_student.php?id=" . $id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; }
        .card { border-radius: 1rem; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row g-4">

        <!-- Form Section -->
        <div class="col-lg-6">
            <div class="card p-4">
                <h4 class="text-center text-primary">Update Student</h4>
                <?php if (!empty($message)) echo $message; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $student['id'] ?? '' ?>">

                    <div class="mb-2"><label>Full Name</label><input type="text" name="name" value="<?= $student['full_name'] ?? '' ?>" class="form-control" required></div>
                    <div class="mb-2"><label>Email</label><input type="email" name="email" value="<?= $student['email_address'] ?? '' ?>" class="form-control" required></div>
                    <div class="mb-2"><label>Class</label>
                        <select name="class" class="form-select" required>
                            <option value="">--Select--</option>
                            <?php
                            $classes = ['Basic 1','Basic 2','Basic 3','Basic 4','Basic 5','Basic 6'];
                            foreach ($classes as $cls) {
                                $selected = ($student['class'] ?? '') === $cls ? 'selected' : '';
                                echo "<option value='$cls' $selected>$cls</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-2"><label>Address</label><input type="text" name="address" value="<?= $student['address'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>Age</label><input type="number" name="age" value="<?= $student['age'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>State of Origin</label><input type="text" name="state_of_origin" value="<?= $student['state_of_origin'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>LGA of Origin</label><input type="text" name="lga_origin" value="<?= $student['lga_origin'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>State of Residence</label><input type="text" name="state_of_residence" value="<?= $student['state_of_residence'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>LGA of Residence</label><input type="text" name="lga_residence" value="<?= $student['lga_residence'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>Religion</label><input type="text" name="religion" value="<?= $student['religion'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>Phone No</label><input type="text" name="phone_no" value="<?= $student['phone_no'] ?? '' ?>" class="form-control"></div>
                    <div class="mb-2"><label>Passport Photo</label><input type="file" name="passport_photo" class="form-control" accept="image/*"></div>
                    <div class="mb-2"><label>Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($student['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($student['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <a href=""> <button class="btn btn-success w-100" name="submit">Update Student</button></a>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
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
                            <td><?= htmlspecialchars($row['class']) ?></td>
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
