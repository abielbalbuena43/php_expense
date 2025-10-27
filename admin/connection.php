<?php
$host = "sql110.infinityfree.com"; 
$username = "if0_40217329";
$password = "dragoon070973"; 
$database = "if0_40217329_php_expense";

$link = mysqli_connect($host, $username, $password, $database);

if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
