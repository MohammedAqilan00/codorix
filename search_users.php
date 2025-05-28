<?php
session_start();
require_once 'db.php'; // ملف الاتصال بقاعدة البيانات $mysqli

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

// تحضير متغيرات للبحث
$users = [];

// إذا كان المستخدم كتب أكثر من كلمة (مثلاً: "محمد أحمد")
$parts = preg_split('/\s+/', $q);

if (count($parts) > 1) {
    // بحث في الاسم الأول والاسم الأخير معاً
    $firstName = $parts[0] . '%';
    $lastName = $parts[1] . '%';

    $sql = "SELECT username, first_name, last_name, profile_picture FROM users 
            WHERE (first_name LIKE ? AND last_name LIKE ?) 
            LIMIT 20";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $firstName, $lastName);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

} else {
    // كلمة واحدة فقط، نبحث في اليوزرنيم والاسم الأول والاسم الأخير
    $like = $q . '%';

    $sql = "SELECT username, first_name, last_name, profile_picture FROM users 
            WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ?
            LIMIT 20";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

// حفظ البحث في الجلسة (recent_searches)
if (!isset($_SESSION['recent_searches'])) {
    $_SESSION['recent_searches'] = [];
}

// نحاول تجنب التكرار
if ($q !== '' && !in_array($q, $_SESSION['recent_searches'])) {
    $_SESSION['recent_searches'][] = $q;
    if (count($_SESSION['recent_searches']) > 10) {
        array_shift($_SESSION['recent_searches']);
    }
}

echo json_encode($users);
