<?php
session_start();
require_once "db.php";

$user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("
    SELECT u.id, u.first_name, u.last_name, u.profile_picture 
    FROM followers f
    JOIN users u ON f.followed_id = u.id
    WHERE f.follower_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>أتابع</h2>
<ul>
<?php while($row = $result->fetch_assoc()): ?>
    <li>
        <img src="<?= htmlspecialchars($row['profile_picture']) ?>" width="50" height="50" style="border-radius:50%">
        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
    </li>
<?php endwhile; ?>
</ul>
