<?php
session_start();
require_once('../login/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = (int)($_GET['user_id'] ?? 0);

// このユーザーをフォローしている一覧
$stmt = $pdo->prepare("
    SELECT u.user_id, u.u_name, u.u_name_id, u.pro_img
    FROM Follow f
    JOIN User u ON f.follower_id = u.user_id
    WHERE f.followed_id = :id
");
$stmt->execute([':id' => $user_id]);
$follower_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>フォロワー一覧</title>
    <style>
        /* ここにCSS */
    </style>
</head>
<header>
    <?php include '../navigation/nav.php'; ?>
</header>
<body>
<main>
    <h1>フォロワー一覧</h1>

    <?php if (count($follower_list) > 0): ?>
        <?php foreach ($follower_list as $row): ?>
            <div>
                <img src="<?= htmlspecialchars($row['pro_img'] ?? 'u_img/default.png') ?>" width="50" style="border-radius:50%;">
                <a href="profile.php?user_id=<?= $row['user_id'] ?>">
                    <?= htmlspecialchars($row['u_name']) ?> (@<?= htmlspecialchars($row['u_name_id']) ?>)
                </a>
            </div>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>フォロワーはいません。</p>
    <?php endif; ?>
</main>
</body>
</html>
