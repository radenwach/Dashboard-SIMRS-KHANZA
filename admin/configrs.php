<?php
$servername = "servername"; 
$username = "username"; 
$password = "pass"; 
$dbname = "sik"; 


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>