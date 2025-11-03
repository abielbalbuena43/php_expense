<?php
$host = "localhost";
$user = "root";
$pass = ""; // usually empty in XAMPP
$db   = "php_expense"; // use your local database name

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
