<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'mohammed_ps';

// أنشئ الاتصال باستخدام $mysqli
$mysqli = new mysqli($host, $user, $password, $dbname);

// التحقق من الاتصال
if ($mysqli->connect_error) {
    die("فشل الاتصال: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");
?>
