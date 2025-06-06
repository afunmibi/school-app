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

// Get admin image from DB or fallback to default
$admin_image = !empty($admin_data['profile_photo']) ? $admin_data['profile_photo'] : 'default.png';

// Fetch teachers and classes for assignment
$teachers = $conn->query("SELECT id, full_name, class_assigned FROM users WHERE role='teacher'");
$classes = $conn->query("SELECT DISTINCT class_assigned FROM students WHERE class_assigned IS NOT NULL AND class_assigned != ''");

// Fetch all assignments for admin view
$assignments = [];
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.subject, a.details, a.due_date, a.date_posted, u.full_name AS teacher_name, a.class_assigned
    FROM assignments a
    LEFT JOIN users u ON a.teacher_id = u.id
    ORDER BY a.date_posted DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

// Fetch detailed teacher list for display on dashboard
$detailed_teachers_query = "
    SELECT
        u.id AS user_table_id,
        u.full_name AS user_table_full_name,
        u.email AS user_table_email,
        u.username AS user_table_username,
        u.class_assigned AS user_table_class_assigned,
        u.profile_photo AS user_table_profile_photo,
        tp.qualification AS profile_qualification,
        tp.phone_number AS profile_phone_number,
        GROUP_CONCAT(DISTINCT tc.class_assigned ORDER BY tc.class_assigned SEPARATOR ', ') AS all_classes
    FROM users u
    LEFT JOIN teachers t ON u.id = t.teacher_id
    LEFT JOIN teacher_classes tc ON u.id = tc.teacher_id
    LEFT JOIN teacher_profile tp ON u.id = tp.teacher_id
    WHERE u.role = 'teacher'
    GROUP BY u.id
    ORDER BY u.full_name ASC
";
$detailed_teachers_result = $conn->query($detailed_teachers_query);
$detailed_teachers_list = [];
if ($detailed_teachers_result) {
    while ($teacher_row = $detailed_teachers_result->fetch_assoc()) {
        $detailed_teachers_list[] = $teacher_row;
    }
}
$detailed_teachers_result->close();
$fetch_pre_register_students = $conn->query("SELECT * FROM pre_registration1 WHERE status = 'pending' ORDER BY full_name ASC");
$fetch_pre_register_students_list = [];
if ($fetch_pre_register_students) {
    while ($student_row = $fetch_pre_register_students->fetch_assoc()) {
        $fetch_pre_register_students_list[] = $student_row;
    }
}
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
        body { background-color: #f4f6f9; min-height: 100vh; }
        .sidebar {
            background-color: #303f9f; color: white; width: 260px; padding-top: 20px;
            flex-shrink: 0; box-shadow: 2px 0 5px rgba(0,0,0,0.1); min-height: 100vh;
            position: fixed; left: 0; top: 0; z-index: 1040; transition: left 0.3s;
            display: flex; flex-direction: column;
        }
        .sidebar .admin-info { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 20px; }
        .sidebar .admin-info img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid white; }
        .sidebar .admin-info h6 { margin-top: 0; margin-bottom: 5px; font-weight: bold; }
        .sidebar .admin-info p { font-size: 0.9rem; color: #e0f7fa; margin-bottom: 0; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: flex; align-items: center; padding: 15px 20px; text-decoration: none; color: white; transition: background-color 0.3s; }
        .sidebar ul li a i { margin-right: 10px; width: 20px; text-align: center; }
        .sidebar ul li a:hover, .sidebar ul li.active > a { background-color: rgba(255,255,255,0.1); }
        .content { margin-left: 260px; padding: 20px; background-color: #f4f6f9; min-height: 100vh; transition: margin-left 0.3s; }
        .top-bar { background-color: #fff; padding: 15px 20px; margin-bottom: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: flex-end; align-items: center; border-radius: 5px; }
        .top-bar .logout-btn { margin-left: 20px; }
        .main-content { background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .module-heading { color: #303f9f; margin-bottom: 15px; }
        @media (max-width: 991.98px) {
            .sidebar { width: 220px; left: -220px; position: fixed; min-height: 100vh; z-index: 1050; border-radius: 0 1rem 1rem 0; }
            .sidebar.show { left: 0; }
            .sidebar-toggler { display: inline-block; position: fixed; left: 10px; top: 10px; z-index: 1100; background: #303f9f; color: #fff; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 1.5rem; line-height: 1; }
            .overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(30,41,59,0.4); z-index: 1049; }
            .overlay.show { display: block; }
            .content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>
<button class="sidebar-toggler d-lg-none" id="sidebarToggle">&#9776;</button>
<div class="overlay" id="sidebarOverlay"></div>
<div class="sidebar" id="sidebarNav">
    <div class="admin-info">
        <img src="../uploads/<?= htmlspecialchars($admin_image); ?>" alt="Admin Image">
        <h6><?= htmlspecialchars($admin_data['full_name'] ?? 'Admin'); ?></h6>
        <p>Administrator</p>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="add_teacher.php"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a></li>
        <li><a href="add_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a></li>
        <li><a href="approve_results.php"><i class="fas fa-check"></i> Approve Results</a></li>
        <li><a href="manage_results.php"><i class="fas fa-check"></i> Results Management</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> View/Edit Profile</a></li>
        <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
    manage_results.php
</div>
<div class="content">
    <div class="top-bar">
        <div class="user-info d-none d-md-flex align-items-center">
            <img src="../uploads/<?= htmlspecialchars($admin_image); ?>" alt="Admin Image" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
            <span><?= htmlspecialchars($admin_data['full_name'] ?? 'Admin'); ?></span>
        </div>
        <a href="../../logout.php" class="btn btn-danger logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="main-content">
        <h4 class="text-primary module-heading">Assign Teacher to Class</h4>
        <form method="POST" action="assign_teacher_class.php" class="row g-3 mb-4">
            <div class="col-md-5">
                <label for="teacher_id" class="form-label">Select Teacher</label>
                <select name="teacher_id" class="form-select" required>
                    <option value="">-- Select Teacher --</option>
                    <?php $teachers->data_seek(0); while ($t = $teachers->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['full_name']) ?><?= $t['class_assigned'] ? " (Current: " . htmlspecialchars($t['class_assigned']) . ")" : "" ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="class_assigned" class="form-label">Assign to Class</label>
                <select name="class_assigned" class="form-select" required>
                    <option value="">-- Select Class --</option>
                    <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['class_assigned']) ?>"><?= htmlspecialchars($c['class_assigned']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">Assign</button>
            </div>
        </form>
        <hr>
        <h4 class="text-primary module-heading">Teacher Overview</h4>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Class Assigned</th>
                        <th>Qualification</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($detailed_teachers_list)): ?>
                        <?php $teacher_count = 1; foreach ($detailed_teachers_list as $teacher): ?>
                            <tr>
                                <td><?= $teacher_count++ ?></td>
                                <td>
                                    <?php
                                    $teacher_photo_path = "..teacher1//uploads/" . (!empty($teacher['user_table_profile_photo']) ? htmlspecialchars($teacher['user_table_profile_photo']) : 'default.png');
                                    if (empty($teacher['user_table_profile_photo']) || !file_exists(dirname(__DIR__) . '/uploads/' . $teacher['user_table_profile_photo'])) {
                                        $teacher_photo_path = "https://ui-avatars.com/api/?name=" . urlencode($teacher['user_table_full_name']) . "&background=303f9f&color=fff&size=40";
                                    }
                                    ?>
                                    <img src="<?= $teacher_photo_path ?>" alt="<?= htmlspecialchars($teacher['user_table_full_name']) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                </td>
                                <td><?= htmlspecialchars($teacher['user_table_full_name']) ?></td>
                                <td><?= htmlspecialchars($teacher['user_table_email']) ?></td>
                                <td><?= htmlspecialchars($teacher['user_table_username']) ?></td>
                                <td>
                                    <?php
                                    // Show all classes from teacher_classes if available, else show main class_assigned
                                    if (!empty($teacher['all_classes'])) {
                                        echo htmlspecialchars($teacher['all_classes']);
                                    } elseif (!empty($teacher['user_table_class_assigned'])) {
                                        echo htmlspecialchars($teacher['user_table_class_assigned']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($teacher['profile_qualification'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($teacher['profile_phone_number'] ?: 'N/A') ?></td>
                                <td>
                                    <a href="edit_teacher.php?id=<?= $teacher['user_table_id'] ?>" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_teacher.php?id=<?= $teacher['user_table_id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this teacher? This will remove their record from users, teachers, teacher_classes, and teacher_profile tables.');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No teachers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <hr>
       
        <hr>
        <h4 class="text-primary module-heading">Quick Actions</h4>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="pending_registration.php" class="btn btn-warning w-100 mb-2"><i class="fas fa-clock"></i> Pending Registrations</a>
            </div>
            <div class="col-md-4">
                <a href="approved_registration.php" class="btn btn-success w-100 mb-2"><i class="fas fa-check"></i> Approved Registrations</a>
            </div>
            <div class="col-md-4">
                <a href="rejected_registration.php" class="btn btn-danger w-100 mb-2"><i class="fas fa-times"></i> Rejected Registrations</a>
            </div>
        </div>
    </div>
    <hr>
<h4 class="text-primary module-heading">Pending Pre-Registered Students</h4>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Class Requested</th>
                <th>Date Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($fetch_pre_register_students_list)): ?>
                <?php $s = 1; foreach ($fetch_pre_register_students_list as $student): ?>
                    <tr>
                        <td><?= $s++ ?></td>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td><?= htmlspecialchars($student['email_address']) ?></td>
                        <td><?= htmlspecialchars($student['phone_no']) ?></td>
                        <td><?= htmlspecialchars($student['class_requested'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($student['date_registered'] ?? '') ?></td>
                        <td>
                            <a href="approved_registration.php?id=<?= $student['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this student?');">Approve</a>
                            <a href="rejected_registration.php?id=<?= $student['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this student?');">Reject</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No pending pre-registrations.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div></div>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>