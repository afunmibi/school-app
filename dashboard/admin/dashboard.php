<?php
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
$admin_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

$admin_image = 'path/to/default/admin_image.png'; // Replace with actual path
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .sidebar {
            background-color: #303f9f;
            color: white;
            width: 260px;
            padding-top: 20px;
            flex-shrink: 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1040;
            transition: left 0.3s;
        }
        .sidebar .admin-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        .sidebar .admin-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid white;
        }
        .sidebar .admin-info h6 {
            margin-top: 0;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .sidebar .admin-info p {
            font-size: 0.9rem;
            color: #e0f7fa;
            margin-bottom: 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s ease;
        }
        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .sidebar ul li a:hover, .sidebar ul li.active > a {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar ul li ul {
            padding-left: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            display: none;
        }
        .sidebar ul li.active ul {
            display: block;
        }
        .sidebar ul li ul li a {
            padding: 10px 15px;
            font-size: 0.9rem;
        }
        .sidebar ul li ul li a:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }
        .sidebar-toggler {
            display: none;
        }
        .overlay {
            display: none;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
            background-color: #f4f6f9;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }
        .top-bar {
            background-color: #ffffff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            border-radius: 5px;
        }
        .top-bar .logout-btn {
            margin-left: 20px;
        }
        .main-content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .module-heading {
            color: #303f9f;
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: bold;
            color: #555;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: 220px;
                left: -220px;
                position: fixed;
                min-height: 100vh;
                z-index: 1050;
                border-radius: 0 1rem 1rem 0;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-toggler {
                display: inline-block;
                position: fixed;
                left: 10px;
                top: 10px;
                z-index: 1100;
                background: #303f9f;
                color: #fff;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                font-size: 1.5rem;
                line-height: 1;
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(30, 41, 59, 0.4);
                z-index: 1049;
            }
            .overlay.show {
                display: block;
            }
            .content {
                margin-left: 0;
                padding: 15px;
            }
            .top-bar {
                margin-bottom: 15px;
                justify-content: space-between;
            }
            .user-info {
                display: none;
            }
            .sidebar ul li ul {
                padding-left: 15px;
            }
        }
    </style>
</head>
<body>
<button class="sidebar-toggler d-lg-none" id="sidebarToggle">&#9776;</button>
<div class="overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebarNav">
        <div class="admin-info">
            <img src="<?= htmlspecialchars($admin_image); ?>" alt="Admin Image">
            <h6><?= htmlspecialchars($admin_data['full_name'] ?? 'Admin'); ?></h6>
            <p>Administrator</p>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="has-submenu">
                <a href="#"><i class="fas fa-user-plus"></i> Registrations <i class="fas fa-caret-down ms-auto"></i></a>
                <ul>
                    <li><a href="pending_registration.php"><i class="fas fa-clock"></i> Pending</a></li>
                    <li><a href="approved_registration.php"><i class="fas fa-check"></i> Approved</a></li>
                    <li><a href="rejected_registration.php"><i class="fas fa-times"></i> Rejected</a></li>
                </ul>
            </li>
            <li><a href="../../download_results.php"><i class="fas fa-file-excel"></i> Download Results</a></li>
            <li><a href="add_teacher.php"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a></li>
            <li><a href="add_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a></li>
            <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <div class="content">
        <div class="top-bar">
            <div class="user-info d-none d-md-flex align-items-center">
                <img src="<?= htmlspecialchars($admin_image); ?>" alt="Admin Image" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                <span><?= htmlspecialchars($admin_data['full_name'] ?? 'Admin'); ?></span>
            </div>
            <a href="../../logout.php" class="btn btn-danger logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="main-content">
            <h4 class="text-primary module-heading">Manage Student Registrations</h4>
            <form method="POST" action="approve_registration.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update Registration</button>
            </form>
            <hr class="my-4">
            <h4 class="text-primary module-heading">Approve Student Results</h4>
            <form method="POST" action="approve_results.php">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student ID</label>
                    <input type="text" class="form-control" name="student_id" required>
                </div>
                <a href="approve_results.php" class="btn btn-success w-100 mb-2">Approve Results</a>
            </form>
            <hr class="my-4">
        </div>
    </div>
    <!-- admin profile -->
     <!-- Admin Dashboard - Link to Admin Profile -->
<div class="container mt-5">
    <div class="col-md-10 offset-md-1 bg-white p-4 rounded shadow">
        <h4 class="text-primary mb-4 text-center">
            Welcome, <?= isset($admin_data['name']) && $admin_data['name'] !== null ? htmlspecialchars($admin_data['name']) : 'Admin' ?>
        </h4>

        <!-- Link to Admin Profile -->
        <div class="text-center mb-4">
            <a href="profile.php" class="btn btn-outline-primary">View / Edit Profile</a>
        </div>

        <!-- Other Dashboard Content -->
        <!-- Your other dashboard sections go here -->

    </div>
</div>

    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('sidebarNav');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        document.addEventListener('DOMContentLoaded', function() {
            const registrationLink = document.querySelector('.sidebar ul li.has-submenu > a');
            const registrationSubmenu = registrationLink.nextElementSibling;
            registrationLink.addEventListener('click', function(e) {
                e.preventDefault();
                registrationSubmenu.style.display = registrationSubmenu.style.display === 'block' ? 'none' : 'block';
                this.parentElement.classList.toggle('active');
            });
            document.addEventListener('click', function(event) {
                if (!registrationLink.contains(event.target) && !registrationSubmenu.contains(event.target)) {
                    registrationSubmenu.style.display = 'none';
                    registrationLink.parentElement.classList.remove('active');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>