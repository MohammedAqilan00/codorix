<?php
require 'db.php'; 
header('Content-Type: application/json');

function generateUniqueId($mysqli) {
    do {
        $id = random_int(1000000000, 9999999999);
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    $stmt->close();
    return $id;
}

$response = ['errors' => [], 'success' => false];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $required_fields = ['username','email','password','confirm_password','first_name','last_name','day','month','year','gender'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $response['errors'][$field] = "هذا الحقل مطلوب.";
        }
    }

    if (count($response['errors']) > 0) {
        echo json_encode($response);
        exit;
    }

    // تنظيف وتجهيز البيانات
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $day = intval($_POST["day"]);
    $month = intval($_POST["month"]);
    $year = intval($_POST["year"]);
    $gender = trim($_POST["gender"]);

    // التحقق من التاريخ
    if (!checkdate($month, $day, $year)) {
        $response['errors']['day'] = "تاريخ الميلاد غير صالح.";
        $response['errors']['month'] = "تاريخ الميلاد غير صالح.";
        $response['errors']['year'] = "تاريخ الميلاد غير صالح.";
    }

    // التحقق من الجنس
    $allowed_genders = ['male', 'female', 'other'];
    if (!in_array($gender, $allowed_genders)) {
        $response['errors']['gender'] = "قيمة غير صحيحة.";
    }

    // اسم المستخدم
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response['errors']['username'] = "اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام فقط.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response['errors']['username'] = "اسم المستخدم مستخدم من قبل.";
        }
        $stmt->close();
    }

    // البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = "صيغة البريد الإلكتروني غير صحيحة.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response['errors']['email'] = "البريد الإلكتروني مستخدم مسبقًا.";
        }
        $stmt->close();
    }

    // التحقق من كلمة المرور
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
        $response['errors']['password'] = "كلمة المرور ضعيفة: يجب أن تحتوي على حرف كبير، صغير، رقم، ورمز.";
    }

    if ($password !== $confirm_password) {
        $response['errors']['confirm_password'] = "كلمتا المرور غير متطابقتين.";
    }

    // حساب العمر
    $birthdate = "$year-$month-$day";
    $dob = DateTime::createFromFormat('Y-n-j', $birthdate);
    if (!$dob) {
        $response['errors']['day'] = "تاريخ الميلاد غير صالح.";
        $response['errors']['month'] = "تاريخ الميلاد غير صالح.";
        $response['errors']['year'] = "تاريخ الميلاد غير صالح.";
    } else {
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        if ($age < 10) {
            $response['errors']['year'] = "العمر يجب ألا يقل عن 10 سنوات.";
        }
    }

    // إذا لا توجد أخطاء، قم بالحفظ
    if (count($response['errors']) === 0) {
        $user_id = generateUniqueId($mysqli);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare("INSERT INTO users (id, username, email, first_name, last_name, password, birthdate, gender) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $username, $email, $first_name, $last_name, $hashed_password, $birthdate, $gender);

        if ($stmt->execute()) {
            // *** بداية كود إنشاء المجلدات بعد نجاح التسجيل ***
            $base_dir = __DIR__ . "/users_data/" . $user_id;
            $subfolders = ['posts', 'reels', 'communities', 'projects', 'repos', 'friends' , 'img_profile'];

            if (!file_exists($base_dir)) {
                mkdir($base_dir, 0755, true);
            }

            foreach ($subfolders as $folder) {
                $path = $base_dir . '/' . $folder;
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
            }
            // *** نهاية كود إنشاء المجلدات ***

            $response['success'] = true;
        } else {
            $response['errors']['general'] = "فشل في إنشاء الحساب: " . $stmt->error;
        }
        $stmt->close();
    }

    echo json_encode($response);
    exit;
}
?>

