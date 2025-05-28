<?php
header("Content-Type: application/json");

$mysqli = new mysqli("localhost", "root", "", "mohammed_ps");

if ($mysqli->connect_error) {
    echo json_encode(["valid" => false, "error" => "DB connection failed"]);
    exit;
}

// التحقق من البريد الإلكتروني
if (isset($_POST['email'])) {
    $email = $mysqli->real_escape_string($_POST['email']);
    $query = "SELECT id FROM users WHERE email = '$email' LIMIT 1";
    $result = $mysqli->query($query);

    if ($result && $result->num_rows > 0) {
        echo json_encode(["valid" => false]); // غير متاح
    } else {
        echo json_encode(["valid" => true]); // متاح
    }
} else {
    echo json_encode(["valid" => false]);
}

$mysqli->close();
?>
