<?php
$host = "localhost";
$user = "your_db_username";
$pass = "your_db_password";
$dbname = "dreamy";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>