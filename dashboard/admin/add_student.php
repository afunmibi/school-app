<?php
session_start();
include "../../config.php";

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $class    = trim($_POST['class']);
    // $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt_check = $conn->prepare("SELECT id FROM students WHERE email_address = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email already exists. <a href='add_student.php' class='alert-link'>Go back</a></div>";
    } else {
        // Generate unique student_id (e.g., STU12345)
        $prefix = "STU";
        $rand_num = rand(10000, 99999);
        $student_id = $prefix . $rand_num;

        $stmt = $conn->prepare("INSERT INTO students (full_name, email_address, class, student_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $class, $student_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Student added successfully. <a href='add_student.php' class='alert-link'>Add another</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding student. Please try again.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 g-4 align-items-start">
            <!-- Add Student Form -->
            <div class="col">
                <div class="card p-4 h-100">
                    <h3 class="text-center text-primary mb-4">Add Student</h3>
                    <?php if (!empty($message)) echo $message; ?>
                    <form method="POST" action="add_student.php" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" name="name" id="name" required class="form-control" placeholder="Enter full name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" required class="form-control" placeholder="Enter email address">
                        </div>
                        <div class="mb-3">
                            <label for="class" class="form-label">Class</label>
                            <select name="class" id="class" required class="form-control">
                                <option value="">--Select Class--</option>
                                <option value="Basic 1">Basic 1</option>
                                <option value="Basic 2">Basic 2</option>
                                <option value="Basic 3">Basic 3</option>
                                <option value="Basic 4">Basic 4</option>
                                <option value="Basic 5">Basic 5</option>
                                <option value="Basic 6">Basic 6</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success mt-2 w-100">Add Student</button>
                    </form>
                </div>
                <div class="text-center mt-4">
                        <a href="./dashboard.php" class="btn btn-danger">Go Back</a>
                    </div>
            </div>

            <!-- Students Table -->
            <div class="col">
                <div class="card p-4 h-100">
                    <h4 class="text-primary mb-4">All Students</h4>
                    <table class="table table-bordered mt-3">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Student ID</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sn = 1;
                            $query = "SELECT * FROM students ORDER BY id DESC";
                            $result = $conn->query($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $sn++; ?></td>
                                <td><?= htmlspecialchars($row['full_name']); ?></td>
                                <td><?= htmlspecialchars($row['email_address']); ?></td>
                                <td><?= htmlspecialchars($row['class']); ?></td>
                                <td><?= htmlspecialchars($row['student_id']); ?></td>
                                <td>
                                    <a href="edit_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="delete_student.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-success mt-3">Student updated successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-danger mt-3">Student deleted successfully!</div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <a href="../admin/dashboard.php" class="btn btn-danger">Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
