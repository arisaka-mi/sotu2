<?php
// nav.php
require_once('../login/config.php');

$logged_in_user_id = $_SESSION['user_id'] ?? null;
$user_name = '';
$user_icon = '../profile/u_img/default.png';
$cache_buster = time(); // ← 追加

if ($logged_in_user_id) {
    $stmt = $pdo->prepare("SELECT u_name, pro_img FROM User WHERE user_id = :id");
    $stmt->execute([':id' => $logged_in_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_name = htmlspecialchars($user['u_name'], ENT_QUOTES, 'UTF-8');

        if (!empty($user['pro_img']) && file_exists(__DIR__ . '/../profile/u_img/' . $user['pro_img'])) {
            $user_icon = '../profile/u_img/' . $user['pro_img'];
            $cache_buster = filemtime(__DIR__ . '/../profile/u_img/' . $user['pro_img']);
        }
    }
}


?>

<header class="global-nav" id="sidebar">

    <div class="top-logo">
        <img src="../navigation/img/icon_edge.PNG" alt="logo">
    </div>

    <div class="nav-container">

        <button class="toggle-btn" id="toggleBtn">☰</button>

        <nav class="nav-links">
            <a href="../home/timeline_public.php" data-title="home">
                <img src="../navigation/img/home_edge.PNG" alt="home" class="menu-icon">
                <span class="menu-text">home</span>
            </a>

            <a href="../profile/profile.php" data-title="profile">
                <img src="../navigation/img/default_edge.PNG" alt="profile" class="menu-icon">
                <span class="menu-text">profile</span>
            </a>

            <a href="../search/search.php" data-title="search">
                <img src="../navigation/img/search_edge.PNG" alt="search" class="menu-icon">
                <span class="menu-text">search</span>
            </a>

            <a href="../post/post.php" data-title="post">
                <img src="../navigation/img/post_icon_edge.PNG" alt="post" class="menu-icon">
                <span class="menu-text">post</span>
            </a>

            <a href="../notify/notifications.php" data-title="post">
                <img src="../navigation/img/inform_edge.PNG" alt="inform" class="menu-icon">
                <span class="menu-text">inform</span>
            </a>
        </nav>


        <?php if ($logged_in_user_id): ?>
            <div class="user-info">
                <img src="<?= htmlspecialchars($user_icon) ?>?v=<?= $cache_buster ?>"
                    alt="アイコン"
                    class="user-icon">
                <span class="user-name"><?= $user_name ?></span>
            </div>
        <?php endif; ?>

    </div>
</header>


<style>
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.top-logo{
    position: fixed;
    top: 16px;
    left: 170px;
    z-index: 2000;
}

.top-logo img{
    height: 60px;
}

/* ===== サイドバー ===== */
.global-nav {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    background: #ffffff;
    
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    transition: width 0.3s;
    overflow: hidden;
    border-radius: 0 20px 20px 0;  /* 右上・右下を丸く */
}

/* 折りたたみ状態 */
.global-nav.collapsed {
    width: 120px;
}

/* 中身 */
/* .nav-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 10px;
} */

.nav-container {
    display: flex;
    flex-direction: column;
    height: 100vh;   /* ← ここを変更 */
    padding: 10px;
}


/* トグルボタン */
.toggle-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    margin-bottom: 20px;
}

/* ナビ */
.nav-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* リンク */
.nav-links a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 25px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    white-space: nowrap;
}
/* アイコン画像 */
.menu-icon {
    width: 35px;
    height: 35px;
    object-fit: contain;
    flex-shrink: 0;
}

/* 文字 */
.menu-text {
    transition: opacity 0.2s;
}

/* 折りたたみ時は文字だけ隠す */
.global-nav.collapsed .menu-text {
    opacity: 0;
    pointer-events: none;
}

/* 折りたたみ時は中央寄せ */
.global-nav.collapsed .nav-links a {
    justify-content: center;
}

.nav-links a:hover {
    background: #e7e9f5;
}

/* ユーザー情報 */
.user-info {
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

.user-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    background-color: #eee;   /* 読み込み前対策 */
}


/* 折りたたみ時は文字を隠す */
.global-nav.collapsed .nav-links a,
.global-nav.collapsed .user-name {
    font-size: 0;
}

/* ===== メインコンテンツ ===== */
main {
    margin-left: 220px;
    transition: margin-left 0.3s;
}

.global-nav.collapsed ~ main {
    margin-left: 60px;
}
</style>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleBtn');

// ===== ページ読み込み時に状態を復元 =====
const sidebarState = localStorage.getItem('sidebarState');
if (sidebarState === 'collapsed') {
    sidebar.classList.add('collapsed');
}

// ===== トグルボタン =====
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');

    // 状態を保存
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('sidebarState', 'collapsed');
    } else {
        localStorage.setItem('sidebarState', 'expanded');
    }
});
</script>

