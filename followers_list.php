<?php
session_start();
require 'db.php'; // تأكد أن هذا المسار صحيح حسب مشروعك

// لنفرض أن معرف المستخدم الحالي محفوظ في الجلسة، وإذا لم يكن، اختبر بمعرف ثابت
$currentUserId = $_SESSION['user_id'] ?? 1; // عدل أو استبدل 1 بمعرف مناسب للتجربة

// نوع القائمة التي تريد عرضها: متابعين أو الذين تتابعهم
$type = $_GET['type'] ?? 'followers'; // 'followers' أو 'following'

// استعلام لجلب المستخدمين حسب النوع
if ($type === 'followers') {
    // من يتابعني
    $sql = "SELECT u.id, u.username FROM users u
            INNER JOIN followers f ON u.id = f.follower_id
            WHERE f.followed_id = ?";
} else {
    // الذين أتابعهم
    $sql = "SELECT u.id, u.username FROM users u
            INNER JOIN followers f ON u.id = f.followed_id
            WHERE f.follower_id = ?";
}

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>قائمة <?= $type === 'followers' ? 'المتابعين' : 'الذين أتابعهم' ?></title>
    <link rel="stylesheet" href="profile.css"> <!-- أو أي ملف css تستخدمه -->
</head>
<body>
    <h1>قائمة <?= $type === 'followers' ? 'المتابعين' : 'الذين أتابعهم' ?></h1>

    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <?= htmlspecialchars($user['username']) ?> (ID: <?= $user['id'] ?>)
            </li>
        <?php endforeach; ?>
    </ul>

    <p><a href="profile.php?id=<?= $currentUserId ?>">العودة إلى الملف الشخصي</a></p>
</body>
</html>
