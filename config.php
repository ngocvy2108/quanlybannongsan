<?php
$servername = "localhost";      
$username = "root";            
$password = "";                 
$dbname = "csdldoanchuyennganh";     
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập tiếng Việt
$conn->set_charset("utf8");
?>
