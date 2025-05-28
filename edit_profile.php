
<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

require 'db.php';

$user_id = $_SESSION["user_id"];
$error = "";
$success = "";

// جلب بيانات المستخدم الحالية
$stmt = $mysqli->prepare("SELECT username, first_name, last_name, email, profile_picture, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // تحقق أولي
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email)) {
        $error = "يرجى ملء جميع الحقول المطلوبة.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "البريد الإلكتروني غير صالح.";
    } else {
        // تحقق من وجود اسم المستخدم إذا تم تغييره
        if ($username !== $user['username']) {
            $stmtCheck = $mysqli->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmtCheck->bind_param("s", $username);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            if ($resultCheck->num_rows > 0) {
                $error = "اسم المستخدم مستخدم بالفعل، يرجى اختيار اسم آخر.";
            }
        }
    }

    if (!$error) {
        // رفع الصورة إن وجدت
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = $_FILES['profile_picture']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $newFileName = 'profile_' . $user_id . '.' . $fileExtension;
                $uploadFileDir = './uploads/';
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_picture = $newFileName;
                } else {
                    $error = "حدث خطأ أثناء رفع الصورة.";
                }
            } else {
                $error = "نوع الملف غير مدعوم. يسمح فقط بـ jpg, jpeg, png, gif.";
            }
        } else {
            $profile_picture = $user['profile_picture'];
        }
    }

    if (!$error) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, profile_picture=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssi", $username, $first_name, $last_name, $email, $profile_picture, $hashed_password, $user_id);
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET username=?, first_name=?, last_name=?, email=?, profile_picture=? WHERE id=?");
            $stmt->bind_param("sssssi", $username, $first_name, $last_name, $email, $profile_picture, $user_id);
        }

        if ($stmt->execute()) {
            $success = "تم تحديث الملف الشخصي بنجاح.";
            $user['username'] = $username;
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['email'] = $email;
            $user['profile_picture'] = $profile_picture;
        } else {
            $error = "حدث خطأ أثناء تحديث البيانات: " . $mysqli->error;
        }
    }
}

// تحديد الصورة للعرض: الصورة المرفوعة أو صورة افتراضية حسب الجنس
if (!empty($user['profile_picture']) && file_exists("uploads/" . $user['profile_picture'])) {
    $profile_picture = "uploads/" . $user['profile_picture'];
} else {
    if ($user['gender'] === 'male') {
        $profile_picture = "uploads/xi.png";
    } elseif ($user['gender'] === 'female') {
        $profile_picture = "uploads/xx.png";
    } else {
        $profile_picture = "uploads/xi.jpg";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تعديل الملف الشخصي</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f2f2f2; margin:0; padding:0; }
        .container {
            max-width: 520px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        h2 { text-align: center; margin-bottom: 25px; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;
        }
        #profileImage {
            display: block;
            margin: 10px auto;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid #ccc;
            transition: border-color 0.3s;
        }
        #profileImage:hover { border-color: #e60000; }
        .btn-submit {
            margin-top: 20px;
            width: 100%;
            background-color: #e60000;
            border: none;
            padding: 12px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            border-radius: 8px;
            font-size: 16px;
        }
        .btn-submit:hover { background-color: #b30000; }
        .error {
            color: red;
            margin-top: 15px;
            text-align: center;
        }
        .success {
            color: green;
            margin-top: 15px;
            text-align: center;
        }
        #imageOptions {
            display: none;
            text-align: center;
            margin-bottom: 15px;
        }
        #imageOptions button {
            margin: 0 5px;
            padding: 6px 12px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: white;
            transition: background-color 0.3s;
        }
        #imageOptions button:hover {
            background-color: #e60000;
            color: white;
            border-color: #e60000;
        }
        #backButton {
            position: absolute;
            top: 10px;
            left: 15px;
            background-color: #e60000;
            color: white;
            border: none;
            padding: 8px 14px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        #backButton:hover {
            background-color: #b30000;
        }
        /* النافذة المنبثقة للصورة */
        #popupImageContainer {
            display: none;
            position: fixed;
            top:0; left:0; right:0; bottom:0;
            background-color: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        #popupImageContainer img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 12px;
            box-shadow: 0 0 15px #fff;
        }
    </style>
</head>
<body>
<div class="container">
    <button id="backButton" onclick="window.history.back()">رجوع</button>
    <h2>تعديل الملف الشخصي</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <img id="profileImage" src="<?= htmlspecialchars($profile_picture) ?>" alt="صورة الملف الشخصي" title="اضغط لاختيار خيارات" />

    <div id="imageOptions">
        <button id="viewImageBtn">عرض الصورة</button>
        <button id="changeImageBtn">تغيير الصورة</button>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none" />

        <label for="username">اسم المستخدم:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required />

        <label for="first_name">الاسم الأول:</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required />

        <label for="last_name">اسم العائلة:</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required />

        <label for="email">البريد الإلكتروني:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />

        <label for="password">كلمة المرور الجديدة (اتركه فارغاً إذا لم ترغب بتغييرها):</label>
        <input type="password" id="password" name="password" placeholder="كلمة المرور الجديدة" />

        <button type="submit" class="btn-submit">حفظ التعديلات</button>
    </form>
</div>

<div id="popupImageContainer">
    <img src="" alt="صورة كبيرة" />
</div>

<script>
    const profileImage = document.getElementById('profileImage');
    const imageOptions = document.getElementById('imageOptions');
    const viewImageBtn = document.getElementById('viewImageBtn');
    const changeImageBtn = document.getElementById('changeImageBtn');
    const profilePictureInput = document.getElementById('profile_picture');
    const popupImageContainer = document.getElementById('popupImageContainer');
    const popupImage = popupImageContainer.querySelector('img');

    profileImage.addEventListener('click', () => {
        if (imageOptions.style.display === 'block') {
            imageOptions.style.display = 'none';
        } else {
            imageOptions.style.display = 'block';
        }
    });

    viewImageBtn.addEventListener('click', () => {
        popupImage.src = profileImage.src;
        popupImageContainer.style.display = 'flex';
        imageOptions.style.display = 'none';
    });

    changeImageBtn.addEventListener('click', () => {
        profilePictureInput.click();
        imageOptions.style.display = 'none';
    });

    profilePictureInput.addEventListener('change', (event) => {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    popupImageContainer.addEventListener('click', () => {
        popupImageContainer.style.display = 'none';
    });
</script>
</body>
</html>
