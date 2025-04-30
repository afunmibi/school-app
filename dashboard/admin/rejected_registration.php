<?php
session_start();
include "../../config.php";
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
            <h4 class="text-primary mb-4 text-center">Rejected Student Registration</h4>
            
            <?php 
            // Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
// Reject registration
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Update pre-registered student's status to 'rejected'
    $query_update = "UPDATE pre_registration SET status = 'rejected' WHERE id = ?";
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param("i", $id);
    $stmt_update->execute();

    echo "Student registration rejected.";
} else {
    echo "No student ID provided.";
}
            
            ?>
             <p class="text-center"><a href="./dashboard.php" class="btn btn-danger">Go Back</a></p>
        </div>




</div>
</body>
</html>



