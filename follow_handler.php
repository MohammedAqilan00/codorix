<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['followed_id'])) {
    header("Location: index.html");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$followedId = (int) $_POST['followed_id'];

if ($currentUserId === $followedId) {
    // لا يمكن متابعة نفسك
    header("Location: profile.php?id=$followedId");
    exit();
}

// تحقق من وجود العلاقة
$check = $mysqli->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = ?");
$check->bind_param("ii", $currentUserId, $followedId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // إلغاء المتابعة
    $delete = $mysqli->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
    $delete->bind_param("ii", $currentUserId, $followedId);
    $delete->execute();
    $delete->close();
} else {
    // المتابعة
    $insert = $mysqli->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
    $insert->bind_param("ii", $currentUserId, $followedId);
    $insert->execute();
    $insert->close();
}

$check->close();
header("Location: profile.php?id=$followedId");
exit();
?>
