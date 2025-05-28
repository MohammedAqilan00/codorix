<?php
header("Content-Type: application/json");

$mysqli = new mysqli("localhost", "root", "", "mohammed_ps");

if ($mysqli->connect_error) {
    echo json_encode(["valid" => false, "message" => "فشل الاتصال بقاعدة البيانات"]);
    exit;
}

if (isset($_POST['username'])) {
    $username = $_POST['username'];
    error_log("Received username: " . $username);
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(["valid" => false, "message" => "اسم المستخدم محجوز، اختر اسم آخر."]);
    } else {
        echo json_encode(["valid" => true, "message" => "اسم المستخدم متاح."]);
    }
    $stmt->close();
} else {
    echo json_encode(["valid" => false, "message" => "لم يتم إرسال اسم المستخدم."]);
}

$mysqli->close();
?>
