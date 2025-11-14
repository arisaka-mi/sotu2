<?php
session_start();
$u_name_id = isset($_SESSION['u_name']) ? $_SESSION['u_name'] : '';

if (isset($_SESSION['user_id'])) {
    $msg = 'ようこそ ' . htmlspecialchars($u_name_id, ENT_QUOTES, 'UTF-8') . 'さん';
    // ホーム画面に遷移
    $link1 = '
        <form action="../home/home.html" method="post">
            <button type="submit">今すぐはじめる</button>
        </form>
    ';
    // プロフィール画面に遷移
    $link2 = '<form action="profile.php" method="post">
            <button type="submit">プロフィールに移動</button>
            </form>';
    $style = '
        <style>
            body{
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                 margin: 0;
                font-family: sans-serif;
                }
            h1 {
                font-size: 2em;
                margin-bottom: 6.5em;
                text-align: center;
                }
            button{
                display: block;
                margin: 10px auto;
                width: 330px; /* 👈 ボタンの幅を固定して揃える */
                background: linear-gradient(135deg, #FFF7D4, #FFDDDD);
                color: #333;
                border: none;
                padding: 12px 0; /* 横幅固定なので左右paddingは不要 */
                font-size: 1em;
                border-radius: 30px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                }
            button:hover {
                transform: translateY(-3px);
                box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
                filter: brightness(1.05);
                }

        </style>
    ';
} else {
    $msg = 'ログインしていません...';
    $link1 = '<a href="login_from.php">ログイン</a>';
    $style = '
        <style>
            body {
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                margin: 0;
                font-family: sans-serif;
            }
            h1 {
                font-size: 2em;
                margin-bottom: 1em;
            }
            a {
                color: #007bff;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    ';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>BeautyConnect_ログイン画面</title>
    <?php echo $style; ?>
</head>
<body>
    <main>
        <h1 class="text"><?php echo $msg; ?></h1>
        <p class="url"><?php echo $link1; ?></p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="url"><?php echo $link2; ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
