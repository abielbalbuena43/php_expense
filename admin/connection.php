<?php
// ===========================
// Database Connection
// ===========================

// Database credentials
$host = "localhost";       // Usually localhost for XAMPP
$username = "root";        // Default username in XAMPP
$password = "";            // Default password in XAMPP is empty
$database = "php_expense"; // Your expense tracker database name

// Create the connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: uncomment for debugging
// echo "Connection successful!";
?>
