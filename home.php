<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit();
}

require 'db.php';

$user_id = $_SESSION["user_id"];
$stmt = $mysqli->prepare("SELECT username, email, first_name, last_name, profile_picture, gender FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
    <title>الصفحة الرئيسية</title>
    <link rel="stylesheet" href="home.css">
<style>
        .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-toggle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 60px;
        left: 0; /* ✅ جعل القائمة تظهر من اليسار */
        background-color: white;
        width: 280px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-radius: 12px;
        z-index: 999;
        overflow: hidden;
        font-family: "Segoe UI", sans-serif;
        text-align: right;
    }

    .dropdown-menu .user-info-container {
        padding: 15px;
        border-bottom: 1px solid #ddd;
    }

    .dropdown-menu .user-info {
        display: flex;
        align-items: center;
    }

    .dropdown-menu .user-info img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        margin-left: 10px;
    }

    .dropdown-menu .user-info span {
        font-weight: bold;
        font-size: 15px;
    }

    .dropdown-menu .view-profile {
        background-color: #f0f0f0;
        text-align: center;
        padding: 10px;
        font-size: 14px;
        cursor: pointer;
        border-radius: 6px;
        margin: 10px;
        transition: background 0.2s ease;
    }

    .dropdown-menu .view-profile:hover {
        background-color: #e0e0e0;
    }

    .dropdown-menu .menu-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        font-size: 15px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .dropdown-menu .menu-item:hover {
        background-color: #f7f7f7;
    }

    .dropdown-menu .menu-item i {
        margin-left: 10px;
        color: #555;
    }

    .dropdown-menu .divider {
        border-top: 1px solid #eee;
        margin: 5px 0;
    }

    .verified-svg-dropdown {
        
        filter: drop-shadow(1px 0 0 rgba(255, 217, 0, 0.89));
        /* تدرج لوني ذهبي */
        fill: url(#gold-gradient-dropdown);
        transition: fill 0.3s ease;
    }
</style>
</head>
<body>

<header id="topbar">
    <div class="site-info">
        <img src="./uploads/photo.png" alt="شعار الموقع" id="site-logo" />
        <span id="site-name">NOSMP</span>
    </div>
</header>


<div id="sidebar" aria-label="الشريط الجانبي">
    <div id="search-wrapper">
        <button id="search-btn" aria-label="Ctrl K">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="icon-xl-heavy">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M10.75 4.25C7.16015 4.25 4.25 7.16015 4.25 10.75C4.25 14.3399 7.16015 17.25 10.75 17.25C14.3399 17.25 17.25 14.3399 17.25 10.75C17.25 7.16015 14.3399 4.25 10.75 4.25ZM2.25 10.75C2.25 6.05558 6.05558 2.25 10.75 2.25C15.4444 2.25 19.25 6.05558 19.25 10.75C19.25 12.7369 18.5683 14.5645 17.426 16.0118L21.4571 20.0429C21.8476 20.4334 21.8476 21.0666 21.4571 21.4571C21.0666 21.8476 20.4334 21.8476 20.0429 21.4571L16.0118 17.426C14.5645 18.5683 12.7369 19.25 10.75 19.25C6.05558 19.25 2.25 15.4444 2.25 10.75Z" fill="currentColor"></path>
            </svg>
        </button>

        <div class="dropdown">
            
            <img id="user-profile-img" src="<?= htmlspecialchars($profile_picture) ?>" alt="صورة المستخدم" class="dropdown-toggle" title="القائمة" />
            



            <div class="dropdown-menu" id="dropdown-menu">
                <div class="user-info-container">
                    <div class="user-info">
                        <img src="<?= htmlspecialchars($profile_picture) ?>" alt="صورة المستخدم">
                        <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
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

<main>
    <h1>1</h1> 
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
