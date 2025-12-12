<?php
// nav.php
require_once('../login/config.php');

$logged_in_user_id = $_SESSION['user_id'] ?? null;
$user_name = '';
$user_icon = 'u_img/default.png';

if ($logged_in_user_id) {
    $stmt = $pdo->prepare("SELECT u_name, pro_img FROM User WHERE user_id = :id");
    $stmt->execute([':id' => $logged_in_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');
        $user_icon = $user['pro_img'] ?: 'u_img/default.png';
    }
}
?>

<header class="global-nav">
    <div class="nav-container">

        <nav class="nav-links">
            <a href="../home/timeline_public.php">home</a>
            <a href="../profile/profile.php">profile</a>
            <a href="../search/search.php">search</a>
            <a href="../post/post.php">post</a>
        </nav>
        <?php if ($logged_in_user_id): ?>
            <div class="user-info">
                <img src="../profile/<?= htmlspecialchars($user_icon) ?>" alt="アイコン" class="user-icon">
                <span><?= $user_name ?></span>
            </div>
        <?php endif; ?>
    </div>
</header>

<!--仮置き・変更の可能性大-->
<style>
.global-nav {
    width: 100%;
    background-color: #333;
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
}

.nav-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 20px;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
}

.nav-links a {
    color: #fff;
    text-decoration: none;
    margin-left: 20px;
}

.nav-links a:hover {
    color: #ffcc00;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    color: #fff;
}

.user-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}
</style>
