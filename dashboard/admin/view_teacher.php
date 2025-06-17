<?php
session_start();
include "../../config.php";

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}


?>
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
        .teacher-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
                <!-- Table Column -->
        <div class="container mx-auto">
        <div class="row"></div>
            <div class="col-lg-8">
                <div class="table-heading">All Teachers</div>
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
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
                            <td>
                                <?php if (!empty($row['profile_photo'])): ?>
                                    <img src="../../uploads/teachers/<?= htmlspecialchars($row['profile_photo']) ?>" alt="Photo" class="teacher-photo">
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
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
            <a href="dashboard.php" class="btn btn-primary btn-sm">Dashboard </a>
        </div>
        
</body>
</html>