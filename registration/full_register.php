<?php
session_start();
include "../config.php";

// Redirect if not logged in as student or no student session
// if (!isset($_SESSION['student_id'])) {
//     header("Location: ../login.php");
//     exit;
// }

// Fetch student pre-registration data (for step 1)
$student_id = $_SESSION['student_id'];
$query = "SELECT * FROM pre_registration WHERE id = (SELECT pre_reg_id FROM student_login WHERE student_id = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

// If the form is submitted (Step 2)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_step2'])) {
    $address = $_POST['address'];
    $age = $_POST['age'];
    $state_of_origin = $_POST['state_of_origin'];
    $lga_of_origin = $_POST['lga_of_origin'];
    $state_of_residence = $_POST['state_of_residence'];
    $lga_of_residence = $_POST['lga_of_residence'];
    $passport = $_FILES['passport']['name'];

    // Upload passport photo
    $passport_path = 'uploads/' . basename($passport);
    move_uploaded_file($_FILES['passport']['tmp_name'], $passport_path);

    // Save student details
    $update_student = $conn->prepare("UPDATE pre_registration SET address = ?, age = ?, state_of_origin = ?, lga_of_origin = ?, state_of_residence = ?, lga_of_residence = ?, passport = ? WHERE id = ?");
    $update_student->bind_param("sssssssi", $address, $age, $state_of_origin, $lga_of_origin, $state_of_residence, $lga_of_residence, $passport_path, $student_data['id']);
    $update_student->execute();
    header("Location: step3.php");
    exit;
}

// Step 2 form
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Registration - Step 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="col-md-8 offset-md-2 p-4 shadow rounded bg-white">
            <h4 class="text-primary mb-4 text-center">Student Full Registration - Step 2</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="address">Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo $student_data['address']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="age">Age</label>
                    <input type="number" name="age" class="form-control" value="<?php echo $student_data['age']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="state_of_origin">State of Origin</label>
                    <input type="text" name="state_of_origin" class="form-control" value="<?php echo $student_data['state_of_origin']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="lga_of_origin">LGA of Origin</label>
                    <input type="text" name="lga_of_origin" class="form-control" value="<?php echo $student_data['lga_of_origin']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="state_of_residence">State of Residence</label>
                    <input type="text" name="state_of_residence" class="form-control" value="<?php echo $student_data['state_of_residence']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="lga_of_residence">LGA of Residence</label>
                    <input type="text" name="lga_of_residence" class="form-control" value="<?php echo $student_data['lga_of_residence']; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="passport">Passport Photograph</label>
                    <input type="file" name="passport" class="form-control" required>
                </div>
                <button type="submit" name="submit_step2" class="btn btn-primary w-100">Proceed to Step 3</button>
            </form>
        </div>
    </div>
</body>
</html>
