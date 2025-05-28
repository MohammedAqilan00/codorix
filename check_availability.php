<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$response = ['available' => true];

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $response['available'] = $stmt->num_rows === 0;
} elseif (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $stmt->store_result();
    $response['available'] = $stmt->num_rows === 0;
}

header('Content-Type: application/json');
echo json_encode($response);
