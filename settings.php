<?php 
$host = "localhost";        // XAMPP runs the server locally
$username = "root";         //Default username for XAMPP's MySQL
$password = "";             //Default password is empty in XAMPP
$database = "cos20031_database_archery";  //Database name

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error()); // Error description if connection fails
}
?>
