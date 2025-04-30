<?php
session_start();
include "../../config.php";

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}


?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
 
    <title>Document</title>
</head>
<body>
    <div class="container mt-5 mx-auto">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Pending Student Registration</h4>

<?php // Fetch pre-registered students
$query = "SELECT * FROM pre_registration WHERE status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Pending Pre-Registrations</h3>";
echo "<table class='table'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Action</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['phone'] . "</td>";
    echo "<td>
            <a href='approved_registration.php?id=" . $row['id'] . "' class='btn btn-success'>Approve</a>
            <a href='rejected_registration.php?id=" . $row['id'] . "' class='btn btn-danger'>Reject</a>
          </td>";
    echo "</tr>";
}
echo "</table>";?>
 <p class="text-center"><a href="./dashboard.php" class="btn btn-danger">Go Back</a></p>
        </div>
</body>
</html>
