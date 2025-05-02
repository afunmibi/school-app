<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'school-app';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$base_url = "http://localhost/PHP-Projects-Here/school-app/"; // adjust accordingly

?>
