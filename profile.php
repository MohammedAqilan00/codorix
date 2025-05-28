<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

require 'db_connection.php'; 

$currentUserId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT id, username, email, first_name, last_name, profile_picture, gender FROM users WHERE id = ?");
if (!$stmt) {
    die("فشل في التحضير للاستعلام: " . $mysqli->error);
}
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    
    echo "المستخدم الحالي غير موجود.";
    exit;
}

$currentUser = $result->fetch_assoc();
$stmt->close();

$profileUserId = null;

if (isset($_GET['user']) && isset($_GET['user_id'])) {
    echo "يرجى تحديد إما اسم المستخدم أو رقم المستخدم، وليس الاثنين معًا.";
    exit;
}

if (isset($_GET['user'])) {
    $username = trim($_GET['user']);
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo "اسم المستخدم غير صالح.";
        exit;
    }

    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        die("فشل في التحضير للاستعلام: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        echo "المستخدم غير موجود.";
        exit;
    }

    $row = $result->fetch_assoc();
    $profileUserId = (int)$row['id'];
    $stmt->close();

} elseif (isset($_GET['user_id'])) {
    $profileUserId = (int)$_GET['user_id'];

    if ($profileUserId <= 0) {
        echo "رقم مستخدم غير صالح.";
        exit;
    }
} else {
    $profileUserId = $currentUserId;
}


$stmt = $mysqli->prepare("SELECT id, username, email, first_name, last_name, profile_picture, gender FROM users WHERE id = ?");
if (!$stmt) {
    error_log("DB Prepare Error: " . $mysqli->error);
    die("حدث خطأ غير متوقع، يرجى المحاولة لاحقًا.");
}
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "المستخدم غير موجود.";
    exit;
}

$profileUser = $result->fetch_assoc();
$stmt->close();
/*
$filename = isset($profileUser['profile_picture']) ? basename($profileUser['profile_picture']) : '';
if (!empty($filename) && preg_match('/^[\w\-.]+\.(jpg|png|jpeg)$/i', $filename) && file_exists("uploads/" . $filename)){
    $profile_picture = "uploads/" . $filename;
} else {
    if ($profileUser['gender'] === 'male') {
        $profile_picture = "uploads/xi.png";
    } elseif ($profileUser['gender'] === 'female') {
        $profile_picture = "uploads/xx.png";
    } else {
        $profile_picture = "uploads/xi.jpg";
    }
}*/
// صورة المستخدم المبحوث عنه
$filenameProfileUser = isset($profileUser['profile_picture']) ? basename($profileUser['profile_picture']) : '';
$genderProfileUser = isset($profileUser['gender']) ? $profileUser['gender'] : '';

if (!empty($filenameProfileUser) && preg_match('/^[\w\-.]+\.(jpg|png|jpeg)$/i', $filenameProfileUser) && file_exists("uploads/" . $filenameProfileUser)) {
    $profile_picture = "uploads/" . $filenameProfileUser;
} else {
    if ($genderProfileUser === 'female') {
        $profile_picture = "uploads/xx.png";
    } else {
        $profile_picture = "uploads/xi.png";
    }
}

// صورة المستخدم الحالي (للشريط الجانبي أو القائمة)
$filenameCurrentUser = isset($currentUser['profile_picture']) ? basename($currentUser['profile_picture']) : '';
$genderCurrentUser = isset($currentUser['gender']) ? $currentUser['gender'] : '';

if (!empty($filenameCurrentUser) && preg_match('/^[\w\-.]+\.(jpg|png|jpeg)$/i', $filenameCurrentUser) && file_exists("uploads/" . $filenameCurrentUser)) {
    $current_profile_picture = "uploads/" . $filenameCurrentUser;
} else {
    if ($genderCurrentUser === 'female') {
        $current_profile_picture = "uploads/xx.png";
    } else {
        $current_profile_picture = "uploads/xi.png";
    }
}



$isFollowing = false;
if ($profileUserId !== $currentUserId) {
    $checkFollow = $mysqli->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND followed_id = ?");
    $checkFollow->bind_param("ii", $currentUserId, $profileUserId);
    $checkFollow->execute();
    $checkFollow->store_result();
    $isFollowing = $checkFollow->num_rows > 0;
    $checkFollow->close();
}

$sql_following = "SELECT COUNT(*) AS count FROM followers WHERE follower_id = ?";
$stmt_following = $mysqli->prepare($sql_following);
$stmt_following->bind_param("i", $profileUserId);
$stmt_following->execute();
$result_following = $stmt_following->get_result();
$following_count = $result_following->fetch_assoc()['count'] ?? 0;
$stmt_following->close();

$sql_followers = "SELECT COUNT(*) AS count FROM followers WHERE followed_id = ?";
$stmt_followers = $mysqli->prepare($sql_followers);
$stmt_followers->bind_param("i", $profileUserId);
$stmt_followers->execute();
$result_followers = $stmt_followers->get_result();
$followers_count = $result_followers->fetch_assoc()['count'] ?? 0;
$stmt_followers->close();

$isOwnProfile = ($profileUserId === $currentUserId);
$mysqli->close();

?>



<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملفك الشخصي</title>
    <link rel="stylesheet" href="profile.css" />
</head>
<body>
<header id="topbar">
    <div class="site-info">
        <img src="./uploads/img.png" alt="شعار الموقع" id="site-logo" />
        <span id="site-name">NOSMP</span>
    </div>
</header>
    <div id="sidebar" aria-label="الشريط الجانبي">
        <div id="search-wrapper">
            <button id="search-btn" aria-label="Ctrl K">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" class="icon-xl-heavy">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10.75 4.25C7.16015 4.25 4.25 7.16015 4.25 10.75C4.25 14.3399 7.16015 17.25 10.75 17.25C14.3399 17.25 17.25 14.3399 17.25 10.75C17.25 7.16015 14.3399 4.25 10.75 4.25ZM2.25 10.75C2.25 6.05558 6.05558 2.25 10.75 2.25C15.4444 2.25 19.25 6.05558 19.25 10.75C19.25 12.7369 18.5683 14.5645 17.426 16.0118L21.4571 20.0429C21.8476 20.4334 21.8476 21.0666 21.4571 21.4571C21.0666 21.8476 20.4334 21.8476 20.0429 21.4571L16.0118 17.426C14.5645 18.5683 12.7369 19.25 10.75 19.25C6.05558 19.25 2.25 15.4444 2.25 10.75Z" fill="currentColor"></path>
                </svg>
            </button>
            <input type="text" id="search-box" placeholder="ابحث..." autocomplete="off" />
            <div id="search-suggestions" class="hidden"></div>



            <div class="dropdown">
            <img id="user-profile-img" src="<?= htmlspecialchars($current_profile_picture) ?>" alt="صورة المستخدم" class="dropdown-toggle" title="القائمة" />


                <div class="dropdown-menu" id="dropdown-menu">
                    <div class="user-info-container">
                        <div class="user-info">
                            <img src="<?= htmlspecialchars($current_profile_picture) ?>" alt="صورة المستخدم">
                            <span><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></span>
                            <span class="verified-icon-dropdown">
                                <svg viewBox="0 0 15 12" width="14" height="14" fill="currentColor" title="حساب تم التحقق منه" class="verified-svg-dropdown" style="--color: var(--always-white);">
                                    <title>حساب تم التحقق منه</title>
                                    <defs>
                                        <linearGradient id="gold-gradient-dropdown" x1="0" y1="1" x2="1" y2="1">
                                        <stop offset="0%" stop-color= #dacb76 /> 
                                        <stop offset="30%" stop-color= #e9ca4d />     
                                        <stop offset="70%" stop-color= #b8860b />     
                                        <stop offset="100%" stop-color= #6b4a00 />    
                                        </linearGradient>                        
                                    </defs>
                                    <g fill-rule="evenodd" transform="translate(-98 -917)">
                                        <path d="m106.853 922.354-3.5 3.5a.499.499 0 0 1-.706 0l-1.5-1.5a.5.5 0 1 1 .706-.708l1.147 1.147 3.147-3.147a.5.5 0 1 1 .706.708m3.078 2.295-.589-1.149.588-1.15a.633.633 0 0 0-.219-.82l-1.085-.7-.065-1.287a.627.627 0 0 0-.6-.603l-1.29-.066-.703-1.087a.636.636 0 0 0-.82-.217l-1.148.588-1.15-.588a.631.631 0 0 0-.82.22l-.701 1.085-1.289.065a.626.626 0 0 0-.6.6l-.066 1.29-1.088.702a.634.634 0 0 0-.216.82l.588 1.149-.588 1.15a.632.632 0 0 0 .219.819l1.085.701.065 1.286c.014.33.274.59.6.604l1.29.065.703 1.088c.177.27.53.362.82.216l1.148-.588 1.15.589a.629.629 0 0 0 .82-.22l.701-1.085 1.286-.064a.627.627 0 0 0 .604-.601l.065-1.29 1.088-.703a.633.633 0 0 0 .216-.819">

                                        </path>
                                    </g>
                                </svg>
                            </span>
                        </div>
                        <div class="view-profile" onclick="window.location.href='profile.php'">👁‍🗨 عرض ملفي الشخصي</div>

                    </div>
                    <div class="menu-item" onclick="window.location.href='settings.php'">
                        الإعدادات والخصوصية
                        <i class="fas fa-gear"></i>
                    </div>
                    <div class="menu-item">
                        المساعدة والدعم
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="divider"></div>
                    <div class="menu-item" onclick="window.location.href='logout.php'">
                        تسجيل الخروج
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>




<?php if ($isOwnProfile): ?>
    <div id="profile">
        <div class="profile-header">
        <img src="<?= htmlspecialchars($current_profile_picture) ?>" class="main-profile-img" alt="صورة الملف الشخصي" />    

            <div class="profile-info-wrapper">
                <div class="profile-name">
                    <span class="verified-icon-profile">
                        <svg viewBox="0 0 12 13" width="20" height="20" fill="currentColor" title="حساب تم التحقق منه" class="verified-svg-profile" style="--color: var(--always-white);">
                            <title>حساب تم التحقق منه</title>
                            <defs>
                                <linearGradient id="gold-gradient-profile" x1="0" y1="1" x2="1" y2="1">
                                <stop offset="0%" stop-color= #dacb76 /> 
                                <stop offset="30%" stop-color= #e9ca4d />     
                                <stop offset="70%" stop-color= #b8860b />     
                                <stop offset="100%" stop-color= #6b4a00 />    
                                </linearGradient>                        
                            </defs>
                            <g fill-rule="evenodd" transform="translate(-98 -917)">
                                <path d="m106.853 922.354-3.5 3.5a.499.499 0 0 1-.706 0l-1.5-1.5a.5.5 0 1 1 .706-.708l1.147 1.147 3.147-3.147a.5.5 0 1 1 .706.708m3.078 2.295-.589-1.149.588-1.15a.633.633 0 0 0-.219-.82l-1.085-.7-.065-1.287a.627.627 0 0 0-.6-.603l-1.29-.066-.703-1.087a.636.636 0 0 0-.82-.217l-1.148.588-1.15-.588a.631.631 0 0 0-.82.22l-.701 1.085-1.289.065a.626.626 0 0 0-.6.6l-.066 1.29-1.088.702a.634.634 0 0 0-.216.82l.588 1.149-.588 1.15a.632.632 0 0 0 .219.819l1.085.701.065 1.286c.014.33.274.59.6.604l1.29.065.703 1.088c.177.27.53.362.82.216l1.148-.588 1.15.589a.629.629 0 0 0 .82-.22l.701-1.085 1.286-.064a.627.627 0 0 0 .604-.601l.065-1.29 1.088-.703a.633.633 0 0 0 .216-.819"></path>
                            </g>
                        </svg>
                    </span>
                    <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                    
                </div>
                <div class="follow-info">
                    <div class="follow-item">
                        <a href="following.php">أتابع</a>
                        <span class="count"><?= $following_count ?></span>
                    </div>
                    <div class="follow-item">
                        <a href="followers.php">المتابعون</a>
                        <span class="count"><?= $followers_count ?></span>
                    </div>
                </div>
               <!-- <?php if ($currentUser['id'] === $_SESSION['user_id']): ?>
                    <a href="edit_profile.php" class="btn-edit-profile">تعديل الملف الشخصي</a>
                <?php endif; ?>-->
            </div>
        </div>
    </div>

<div class="tabs-container-user">
    <button class="tab-user" onclick="openTab('posts')">المنشورات</button>
    <button class="tab-user" onclick="openTab('reels')">الريلزات</button>
    <button class="tab-user" onclick="openTab('communities')">المجتمعات</button>
    <button class="tab-user" onclick="openTab('projects')">المشاريع</button>
    <button class="tab-user" onclick="openTab('repos')">المستودعات</button>

    <!-- زر المزيد -->
    <div class="tab-more-wrapper-user" style="position: relative;">
        <button onclick="toggleMoreMenu()" class="tab-more-button">
            المزيد
            <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
                <path d="M10 14a1 1 0 0 1-.755-.349L5.329 9.182a1.367 1.367 0 0 1-.205-1.46A1.184 1.184 0 0 1 6.2 7h7.6a1.18 1.18 0 0 1 1.074.721 1.357 1.357 0 0 1-.2 1.457l-3.918 4.473A1 1 0 0 1 10 14z"></path>
            </svg>
        </button>

        <!-- القائمة المنسدلة -->
        <div id="moreDropdown" class="more-dropdown hidden">
            <a href="friends.php"><i class="icon-users"></i> الأصدقاء</a>
            <a href="settings.php"><i class="icon-settings"></i> الإعدادات</a>
        </div>
    </div>
</div>


<!-- محتوى التبويبات -->
<div class="tab-content" id="posts">
    <!-- هنا تضع كود عرض المنشورات (PHP أو HTML ثابت أو JavaScript) -->
    <h3>المنشورات</h3>
    <p>هنا يتم عرض منشورات هذا المستخدم.</p>
</div>

<div class="tab-content hidden" id="reels">
    <h3>الريلزات</h3>
    <p>هنا يتم عرض الريلزات.</p>
</div>

<div class="tab-content hidden" id="communities">
    <h3>المجتمعات</h3>
</div>

<div class="tab-content hidden" id="projects">
    <h3>المشاريع</h3>
</div>

<div class="tab-content hidden" id="repos">
    <h3>المستودعات</h3>
</div>
<style>
.tab-content {
    margin-top: 15px;
}

.hidden {
    display: none;
}

.more-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 8px 0;
    width: 180px;
    z-index: 1000;
    display: none;
    flex-direction: column;
}

.more-dropdown a {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s;
}

.more-dropdown a:hover {
    background-color: #f0f0f0;
    color: #000;
}

.icon-users::before {
    content: "👥";
    margin-left: 8px;
}

.icon-settings::before {
    content: "⚙️";
    margin-left: 8px;
}

.hidden {
    display: none;
}




.tabs-container-user {
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    gap: 25px;
    border-bottom: 2px solid #ccc;
    direction: ltr;
    margin-left: 250px;
    font-family: sans-serif;
}

.tab-user {
    padding: 10px 15px;
    text-decoration: none;
    color: hsl(0, 0.00%, 20.00%);
    font-weight: bold;
    border-bottom: none;
    transition: box-shadow 0.3s ease, background-color 0.3s ease;
    border-radius: 6px;
}

.tab-user:hover {
    box-shadow: 0 0 10px 4px rgba(0, 0, 0, 0.1);
    background-color: hsl(0, 0.00%, 97.60%);
}

.tab-more-button {
    padding: 10px 15px;
    text-decoration: none;
    color: hsl(0, 0.00%, 20.00%);
    font-weight: bold;
    border-bottom: none;
    transition: box-shadow 0.3s ease, background-color 0.3s ease;
    border-radius: 6px;
}

.tab-more-button:hover {
    box-shadow: 0 0 10px 4px rgba(0, 0, 0, 0.1);
    background-color: hsl(236, 100.00%, 58.00%);
}

svg {
    vertical-align: middle;
    fill: hsl(0, 0.00%, 33.30%);
}

</style>
<script>
function openTab(tabId) {
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(div => div.classList.add('hidden'));

    document.getElementById(tabId).classList.remove('hidden');
}

function toggleMoreMenu() {
    const menu = document.getElementById("moreDropdown");
    menu.style.display = menu.style.display === "flex" ? "none" : "flex";
}

// ✅ فتح تبويب "المنشورات" تلقائيًا عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function () {
    openTab('posts');
});
</script>


    <?php else:?>
        <div id="profile-user">
            <div class="profile-header-user">
            
            <img class="main-profile-img-user" src="<?= htmlspecialchars($profile_picture) ?>" alt="صورة المستخدم">

                <div class="profile-info-wrapper-user">
                    <div class="profile-info-wrapper-user">
                        <div class="profile-name-user">
                            <form method="post" action="follow_handler.php" style="margin-top: 10px;">
                                <input type="hidden" name="followed_id" value="<?= intval($profileUserId) ?>">
                                
                            </form>
                            <?= htmlspecialchars($profileUser['first_name'] . ' ' . $profileUser['last_name']) ?>
                                    
                        </div>
                            
                        <div class="following-info-user">
                            <form method="post" action="follow_handler.php">
                                <input type="hidden" name="followed_id" value="<?= $profileUserId ?>">
                                <button id="follow" type="submit">
                                    <?= $isFollowing ? "إلغاء المتابعة" : "متابعة" ?>
                                </button>
                            </form>

                            <div class="follow-info-user">
                                
                                <div class="follow-item-user">
                                    <a href="following.php">أتابع</a>
                                    <span class="count-user"><?= $following_count ?></span>
                                </div>
                                <div class="follow-item-user">
                                    <a href="followers.php">المتابعون</a>
                                    <span class="count-user"><?= $followers_count ?></span>
                                </div>
                                
                                
                                <!--<a href="#" sytle="border-bottom: none;">@<?= htmlspecialchars($profileUser['username']) ?></a>-->
                            </div>
                        </div>
                        

                             
                    </div>

                </div>
            </div>
        </div>
<style>
#profile-user {
    margin-left: 20px;
    padding: 20px;
    padding-top: 40px;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

.profile-header-user {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-left: 250px;
    flex-direction: row-reverse;
}

.main-profile-img-user {
    width: 170px;
    height: 170px;
    border-radius: 100%;
    object-fit: cover;
    border: 3px solid hsl(0, 0.00%, 0.00%);
}

.profile-info-wrapper-user {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
    margin-left: 0;
    transform: translateY(-10px);
    /* لرفع الاسم والروابط قليلاً */
}

.profile-name-user {
    font-size: 33px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0;
    margin-left: 40px;
    /* المسافة بين الصورة والاسم */
}

.following-info-user {
    display: flex;
    flex-direction: row;
    gap: 40px;
    align-items: center;

}

#follow {
    font-size: <?= $isFollowing ? '15px' :  '20px'?>;
    padding: <?= $isFollowing ? '6px 12px' :  '5px 10px'?>;
    background-color: <?= $isFollowing ? 'rgb(34, 34, 34)' : ' #007bff' ?>;
    color: <?= $isFollowing ? 'rgb(255, 255, 255)' : 'rgb(0, 0, 0)' ?>;
    border: none;
    border-radius: 10px;
    cursor: pointer;"
}

.follow-info-user {
    display: flex;
    flex-direction: row;
    gap: 40px;
    font-weight: bold;
    color: #1877f2;
}

.follow-item-user {
    display: flex;
    flex-direction: column;
    /* عمودي */
    align-items: center;
    /* لتوسيط النص والرقم */
}

.follow-item-user a {
    text-decoration: none;
    color: #1877f2;
    font-weight: bold;
    font-size: 17px;
    padding: 8px 12px;
    transition: background-color 0.3s ease;
    border-radius: 6px;
}

.follow-item-user a:hover {
    background-color: #f0f0f0;
}

.count-user {
    font-size: 22px;
    color: #000;
    margin-top: 4px;
    /* مسافة بين النص والرقم */
}

.tabs-container-user {
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    gap: 25px;
    border-bottom: 2px solid #ccc;
    direction: ltr;
    margin-left: 250px;
    font-family: sans-serif;
}

.tab-user {
    padding: 10px 15px;
    text-decoration: none;
    color: hsl(0, 0.00%, 20.00%);
    font-weight: bold;
    border-bottom: none;
    transition: box-shadow 0.3s ease, background-color 0.3s ease;
    border-radius: 6px;
}

.tab-user:hover {
    box-shadow: 0 0 10px 4px rgba(0, 0, 0, 0.1);
    background-color: hsl(0, 0.00%, 97.60%);
}

.tab-user .active {
    color: hsl(214, 89.30%, 52.20%);
}

.tab-user .more svg {
    margin-left: 5px;
    vertical-align: middle;
    fill: hsl(0, 0.00%, 33.30%);
}
</style>

<div class="tabs-container-user">
    <a href="posts.php" class="tab-user tab-posts">المنشورات</a>
    <a href="reels.php" class="tab-user tab-reels">الريلزات</a>
    <a href="communities.php" class="tab-user tab-communities">مجتمعات</a>
    <a href="projects.php" class="tab-user tab-projects">مشاريع</a>
    <a href="repos.php" class="tab-user tab-repos">مستودعات</a>
    <a href="friends.php" class="tab-user tab-friends">مختبر</a>
    <a href="#" class="tab-user tab-more more">
    المزيد
    <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
      <path d="M10 14a1 1 0 0 1-.755-.349L5.329 9.182a1.367 1.367 0 0 1-.205-1.46A1.184 1.184 0 0 1 6.2 7h7.6a1.18 1.18 0 0 1 1.074.721 1.357 1.357 0 0 1-.2 1.457l-3.918 4.473A1 1 0 0 1 10 14z"></path>
    </svg>
</div> 
<?php endif; ?>

       



<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('search-btn');
    const searchBox = document.getElementById('search-box');
    const suggestionsBox = document.getElementById('search-suggestions');

    searchBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        if (searchBox.classList.contains('open')) {
            searchBox.classList.remove('open');
            searchBox.style.display = 'none';
            hideSuggestions();
        } else {
            searchBox.classList.add('open');
            searchBox.style.display = 'block';
            searchBox.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!searchBox.contains(event.target) && event.target !== searchBtn && !suggestionsBox.contains(event.target)) {
            searchBox.classList.remove('open');
            searchBox.style.display = 'none';
            hideSuggestions();
        }
    });

    function showSuggestions(users) {
    console.log('showSuggestions:', users);

    if (!users.length) {
        suggestionsBox.innerHTML = '<div class="suggestion">لا توجد نتائج</div>';
        suggestionsBox.classList.remove('hidden');
        return;
    }

    suggestionsBox.innerHTML = users.map(user => {
        const imgSrc = user.profile_picture ? 'uploads/' + user.profile_picture : 'default-profile.png';
        return `
            <div class="suggestion" data-username="${user.username}">
                <img src="${imgSrc}" alt="${user.username}" />
                <span>${user.first_name} ${user.last_name} (${user.username})</span>
            </div>
        `;
    }).join('');

    suggestionsBox.classList.remove('hidden');

    // 🔥 إضافة الحدث بعد تعبئة الاقتراحات
    document.querySelectorAll('.suggestion').forEach(item => {
        item.addEventListener('click', () => {
            const username = item.getAttribute('data-username');
            searchBox.value = username;
            hideSuggestions(); // يخفي القائمة بعد الاختيار
        });
    });
}


    function hideSuggestions() {
        suggestionsBox.classList.add('hidden');
        suggestionsBox.innerHTML = '';
    }

    searchBox.addEventListener('input', (e) => {
    const query = e.target.value.trim();

    if (query.length >= 2 && !query.includes(' ')) {
        doSearch(query);
    } else {
        hideSuggestions();
    }
});


    function doSearch(query) {
        console.log('بحث عن:', query);

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        fetch(`search_users.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) throw new Error('خطأ في الاستجابة من الخادم');
                return response.json();
            })
            .then(data => {
                console.log('نتائج البحث:', data);
                showSuggestions(data);
            })
            .catch(err => {
                console.error('خطأ في جلب بيانات البحث:', err);
                hideSuggestions();
            });
    }

    searchBox.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();

        const query = searchBox.value.trim();

        if (query && !query.includes(' ')) {
            // استخدام window.location.assign بدلاً من href
            window.location.assign(`profile.php?user=${encodeURIComponent(query)}`);
        } else {
            console.log('الرجاء كتابة اسم مستخدم بدون مسافات');
            hideSuggestions();
        }
    }
});


    const toggle = document.querySelector('.dropdown-toggle');
    const menu = document.getElementById('dropdown-menu');

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    });

    document.addEventListener('click', function () {
        menu.style.display = 'none';
    });
});

</script>

</body>
</html>