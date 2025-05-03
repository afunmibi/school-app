<?php
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
    $assigned_class = trim($_POST['assigned_class'] ?? '');
    $assigned_subject = trim($_POST['assigned_subject'] ?? '');

    // Check if username or email already exists
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt_check->bind_param("ss", $email, $username);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email or Username already exists. <a href='admin_dashboard.php' class='alert-link'>Go back</a></div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $username, $password, $role);
        if ($stmt->execute()) {
            $teacher_id = $stmt->insert_id;

            // Insert class assignment
            $stmt2 = $conn->prepare("INSERT INTO teacher_classes (teacher_id, assigned_class) VALUES (?, ?)");
            $stmt2->bind_param("is", $teacher_id, $assigned_class);
            $stmt2->execute();

            $message = "<div class='alert alert-success'>Teacher added successfully. <a href='admin_dashboard.php' class='alert-link'>Go back</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding teacher.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Teacher</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .form-label {
            font-weight: 500;
        }
        .btn-success {
            width: 100%;
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .row-cols-lg-2 > .col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row row-cols-1 row-cols-lg-2 g-4 align-items-start">
            <!-- Add Teacher Form -->
            <div class="col">
                <div class="card p-4 h-100">
                    <h3 class="text-center text-primary mb-4">Add Teacher</h3>
                    <?php if (!empty($message)) echo $message; ?>
                    <form method="POST" action="add_teacher.php" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" name="name" id="name" required class="form-control" placeholder="Enter full name">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" required class="form-control" placeholder="Enter email address">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" required class="form-control" placeholder="Choose a username">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" required class="form-control" placeholder="Create a password">
                        </div>
                        <div class="mb-3">
                            <label for="assigned_class" class="form-label">Assign Class</label>
                            <select name="assigned_class" id="assigned_class" required class="form-select">
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
                            <input type="text" name="assigned_subject" class="form-control" placeholder="or e.g. Mathematics" required readonly>
                        </div>
                        <button type="submit" class="btn btn-success mt-2">Add Teacher</button>
                    </form>
                </div>
                <div class="text-center mt-4">
                        <a href="./dashboard.php" class="btn btn-danger">Go Back</a>
                    </div>
            </div>
            
            <!-- All Teachers Table -->
            <div class="col">
                <div class="card p-4 h-100">
                    <h4 class="text-primary mb-4">All Teachers</h4>
                    <table class="table table-bordered mt-3">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Class</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT u.id, u.full_name, u.email, u.username, tc.assigned_class 
                                      FROM users u
                                      LEFT JOIN teacher_classes tc ON u.id = tc.teacher_id
                                      WHERE u.role = 'teacher'";
                            $result = $conn->query($query);
                            $sn = 1;
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= $sn++; ?></td>
                                    <td><?= htmlspecialchars($row['full_name']); ?></td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td><?= htmlspecialchars($row['username']); ?></td>
                                    <td><?= htmlspecialchars($row['assigned_class'] ?? ''); ?></td>
                                    <td>
                                        <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="delete_teacher.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php if (isset($_GET['updated'])): ?>
                        <div class="alert alert-success">Teacher updated successfully!</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['deleted'])): ?>
                        <div class="alert alert-danger">Teacher deleted successfully!</div>
                    <?php endif; ?>
                    <div class="text-center mt-4">
                        <a href="./dashboard.php" class="btn btn-danger">Go Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
