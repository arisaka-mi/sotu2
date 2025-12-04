<?php
//ログイン有効期限
$session_lifetime = 3 * 24 * 60 * 60; // 3日 * 24時間 * 60分 * 60秒

// セッションクッキーの有効期限を延長
session_set_cookie_params([
    'lifetime' => $session_lifetime,
    'path' => '/',
    'domain' => '', // 必要に応じてドメイン指定
    'secure' => isset($_SERVER['HTTPS']), // HTTPSの場合はtrue
    'httponly' => true,
    'samesite' => 'Lax' // Strict / Lax / None
]);
session_start();
$email = isset($_POST['email']) ? $_POST['email'] : '';
$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';

// DB接続設定
require_once('config.php');

$sql = "SELECT * FROM User WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':email', $email);
$stmt->execute();
$member = $stmt->fetch();
//指定したハッシュがパスワードにマッチしているかチェック
if (!$member) {
    $msg = 'メールアドレスが登録されていません。';
    $link = '<a href="login_from.php">戻る</a>';
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

} elseif (!password_verify($pwd, $member['pwd'])) {
    $msg = 'パスワードが間違っています。';
    $link = '<a href="login_from.php">戻る</a>';
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
} else {
    $_SESSION['user_id'] = $member['user_id'];
    $_SESSION['u_name'] = $member['u_name'];
    $_SESSION['u_name_id'] = $member['u_name_id'];
    $_SESSION['pro_img'] = $member['pro_img'];
    $_SESSION['hight'] = $member['hight'];
    $_SESSION['bt_id'] = $member['bt_id'];
    $_SESSION['pc_id'] = $member['pc_id'];
    header("Location: index.php");
    exit();
}
?>

<h1><?php echo $msg; ?></h1>
<?php echo $link; ?>
<?php echo $style; ?>
