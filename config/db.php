<?php
$host = "localhost";
$user = "root";
$pass = "121522";
$dbname = "mcdelivery";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>