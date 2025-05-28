<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mohammed_ps";

$mysqli = new mysqli($servername, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $mysqli->connect_error);
}
?>
