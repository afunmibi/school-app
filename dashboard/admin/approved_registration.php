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
            <h4 class="text-primary mb-4 text-center">Approved Student Registration</h4>

            <?php

// Approve registration
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch pre-registered student data
    $query = "SELECT * FROM pre_registration WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        // Insert into the students table
        $query_insert = "INSERT INTO students (
full_name,
phone,
email,
unique_id,
status
) VALUES (?, ?, ?, ?, 'pending')";
        $stmt_insert = $conn->prepare($query_insert);
        if ($stmt_insert) {
            $unique_student_id = 'STU' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT); // Generate a unique student ID
            $stmt_insert->bind_param("ssss", $row['full_name'], $row['phone'], $row['email'], $unique_student_id);
            if ($stmt_insert->execute()) {
                // Update pre-registered student's status to 'approved'
                $query_update = "UPDATE pre_registration SET status = 'approved' WHERE id = ?";
                $stmt_update = $conn->prepare($query_update);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $id);
                    if ($stmt_update->execute()) {
                        echo "<p class='alert alert-success'>Student registration approved.</p>";
                    } else {
                        echo "<p class='alert alert-danger'>Error updating pre-registration status: " . $stmt_update->error . "</p>";
                    }
                    $stmt_update->close();
                } else {
                    echo "<p class='alert alert-danger'>Error preparing update statement: " . $conn->error . "</p>";
                }
            } else {
                echo "<p class='alert alert-danger'>Error inserting into students table: " . $stmt_insert->error . "</p>";
            }
            $stmt_insert->close();
        } else {
            echo "<p class='alert alert-danger'>Error preparing insert statement: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='alert alert-warning'>Pre-registered student not found with ID: " . htmlspecialchars($id) . "</p>";
    }
    $stmt->close();
} else {
    echo "<p class='alert alert-info'>No student ID provided.</p>";
}

            ?>
            <p class="text-center"><a href="./dashboard.php" class="btn btn-danger">Go Back</a></p>
        </div>
    </div>
</body>
</html>