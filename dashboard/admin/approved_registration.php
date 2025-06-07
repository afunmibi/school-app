<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
require_once "../../config.php";

$message = "";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Handle student approval
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $pre_reg_id = (int)$_GET['id'];
    $conn->begin_transaction();

    try {
        // 🔹 1. Fetch pre-registration data
        $stmt_fetch = $conn->prepare("SELECT * FROM pre_registration1 WHERE id = ? AND status = 'Pending'");
        if (!$stmt_fetch) throw new Exception("Prepare fetch pre_registration1 failed: " . $conn->error);

        $stmt_fetch->bind_param("i", $pre_reg_id);
        $stmt_fetch->execute();
        $pre_reg_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        if (!$pre_reg_data) throw new Exception("Pre-registration record not found or already approved.");

        // Extract details
        extract($pre_reg_data);
        $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);
        $student_id_string = 'SID' . rand(10000, 99999);

        // 🔹 2. Approve pre-registration
        $stmt_update_pre_reg = $conn->prepare("UPDATE pre_registration1 SET status = 'approved' WHERE id = ?");
        if (!$stmt_update_pre_reg) throw new Exception("Prepare update pre_registration1 failed: " . $conn->error);

        $stmt_update_pre_reg->bind_param("i", $pre_reg_id);
        $stmt_update_pre_reg->execute();
        $stmt_update_pre_reg->close();

        // 🔹 3. Insert/Update Students Table
        $stmt_students = $conn->prepare("
            INSERT INTO students (
                registration_id, unique_id, full_name, phone_no, email_address, status,
                address, dob, state_of_origin, lga_origin, state_of_residence, lga_of_residence, parent_name,
                parent_address, parent_occupation, religion, child_comment, birth_certificate, testimonial, passport_photo,
                password, registration_pdf, created_at, class_assigned, student_id
            ) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name), phone_no = VALUES(phone_no), email_address = VALUES(email_address),
                status = VALUES(status), address = VALUES(address), dob = VALUES(dob), state_of_origin = VALUES(state_of_origin),
                lga_origin = VALUES(lga_origin), state_of_residence = VALUES(state_of_residence), lga_of_residence = VALUES(lga_of_residence),
                parent_name = VALUES(parent_name), parent_address = VALUES(parent_address), parent_occupation = VALUES(parent_occupation),
                religion = VALUES(religion), child_comment = VALUES(child_comment), birth_certificate = VALUES(birth_certificate),
                testimonial = VALUES(testimonial), passport_photo = VALUES(passport_photo), password = VALUES(password),
                registration_pdf = VALUES(registration_pdf), class_assigned = VALUES(class_assigned), student_id = VALUES(student_id)
        ");
        if (!$stmt_students) throw new Exception("Prepare students table UPSERT failed: " . $conn->error);

        $stmt_students->bind_param(
            "sssssssssssssssssssssss",
            $registration_id, $unique_id, $full_name, $phone_no, $email_address,
            $address, $dob, $state_of_origin, $lga_origin, $state_of_residence, $lga_of_residence, $parent_name,
            $parent_address, $parent_occupation, $religion, $child_comment, $birth_certificate, $testimonial, $passport_photo,
            $hashed_password, $registration_pdf, $class_assigned, $student_id_string
        );
        $stmt_students->execute();
        if ($stmt_students->affected_rows > 0) {
    echo "<div class='alert alert-info text-center'>Students table updated successfully: " . $stmt_students->affected_rows . " row(s) affected.</div>";
} else {
    echo "<div class='alert alert-warning text-center'>Students table update potentially failed. Affected rows: " . $stmt_students->affected_rows . " Error: " . htmlspecialchars($stmt_students->error) . "</div>";
}
        $stmt_students->close();

        // 🔹 4. Insert/Update Student Login Table
        $stmt_student_login = $conn->prepare("
            INSERT INTO student_login (
                unique_id, email_address, full_name, phone_no, password, created_at, student_id
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                email_address = VALUES(email_address), full_name = VALUES(full_name), phone_no = VALUES(phone_no),
                password = VALUES(password), created_at = VALUES(created_at), student_id = VALUES(student_id)
        ");
        if (!$stmt_student_login) throw new Exception("Prepare student_login table UPSERT failed: " . $conn->error);

        $stmt_student_login->bind_param("ssssss",
            $unique_id, $email_address, $full_name, $phone_no, $hashed_password, $student_id_string
        );
        $stmt_student_login->execute();
        $stmt_student_login->close();

       

        // Commit the transaction
        $conn->commit();

        $message = "<div class='alert alert-success text-center'>Student " . htmlspecialchars($full_name) . " approved and synchronized across tables.</div>";

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Approval Error: " . $e->getMessage(), 3, "/var/log/school_app_errors.log");
        $message = "<div class='alert alert-danger text-center'>Approval failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    header("Location: approved_registration.php?msg=" . urlencode(strip_tags($message)));
    exit;
}

// Fetch all approved students
$approved_students = [];
$query_approved = "SELECT id, full_name, phone_no, email_address, unique_id, status, created_at FROM students WHERE status = 'approved'";
$result_approved = $conn->query($query_approved);

if ($result_approved) {
    while ($row = $result_approved->fetch_assoc()) {
        $approved_students[] = $row;
    }
    $result_approved->free();
} else {
    $message = "<div class='alert alert-danger text-center'>Error fetching approved students: " . htmlspecialchars($conn->error) . "</div>";
}

// Encode approved students data as JSON for JavaScript
$approved_students_json = json_encode($approved_students);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .table thead th {
            background-color: #007bff;
            color: white;
        }
        .dark-mode {
            background-color: #343a40 !important;
            color: white !important;
        }
        .dark-mode .table {
            background-color: #454d55;
            color: white;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="bi bi-person-check-fill"></i> Approved Students</h2>
        <button class="btn btn-outline-dark" onclick="toggleDarkMode()">Toggle Dark Mode</button>
    </div>

    <?php if (!empty($_GET['msg'])): ?>
        <?= $_GET['msg'] ?>
    <?php endif; ?>

    <?php if ($message): ?>
        <?= $message ?>
    <?php endif; ?>

    <div class="card p-4">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by Name, Email, or ID">
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Unique ID</th>
                        <th>Status</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <button class="btn btn-outline-primary" onclick="prevPage()">Previous</button>
            <button class="btn btn-outline-primary" onclick="nextPage()">Next</button>
        </div>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-danger"><i class="bi bi-arrow-left-circle"></i> Go Back</a>
            <button class="btn btn-success" onclick="exportCSV()">Export as CSV</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let allStudents = <?php echo $approved_students_json; ?>;
    let students = [...allStudents]; // Create a copy for filtering
    let currentPage = 1;
    const itemsPerPage = 10;

    function displayStudents() {
        const tbody = document.getElementById("studentTableBody");
        tbody.innerHTML = "";
        let start = (currentPage - 1) * itemsPerPage;
        let end = start + itemsPerPage;

        students.slice(start, end).forEach((student, index) => {
            let row = `<tr>
                <td>${start + index + 1}</td>
                <td>${student.full_name}</td>
                <td>${student.phone_no}</td>
                <td>${student.email_address}</td>
                <td>${student.unique_id}</td>
                <td><span class="badge bg-success">${student.status}</span></td>
                <td>${student.created_at}</td>
            </tr>`;
            tbody.innerHTML += row;
        });
    }

    function searchStudents() {
        let searchTerm = document.getElementById("searchInput").value.toLowerCase();
        students = allStudents.filter(student =>
            student.full_name.toLowerCase().includes(searchTerm) ||
            student.email_address.toLowerCase().includes(searchTerm) ||
            student.unique_id.toLowerCase().includes(searchTerm)
        );
        currentPage = 1;
        displayStudents();
    }

    document.getElementById('searchInput').addEventListener('input', searchStudents);

    function prevPage() {
        if (currentPage > 1) {
            currentPage--;
            displayStudents();
        }
    }

    function nextPage() {
        if (currentPage * itemsPerPage < students.length) {
            currentPage++;
            displayStudents();
        }
    }

    function toggleDarkMode() {
        document.body.classList.toggle("dark-mode");
    }

    function exportCSV() {
        let csvContent = "data:text/csv;charset=utf-8,Full Name,Phone,Email,Unique ID,Status,Registered At\n";
        students.forEach(student => {
            csvContent += `${student.full_name},${student.phone_no},${student.email_address},${student.unique_id},${student.status},${student.created_at}\n`;
        });

        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "approved_students.csv");
        document.body.appendChild(link);
        link.click();
    }

    displayStudents(); // Initial display
</script>

</body>
</html>