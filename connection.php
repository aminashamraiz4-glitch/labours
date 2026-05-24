<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "labour_booking";

$conn = new mysqli($servername, $username, $password, $dbname);

if($conn->connect_error){
    die ("Connection Failed");
}

?>