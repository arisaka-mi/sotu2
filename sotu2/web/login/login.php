<?php
session_start();
$mail = isset($_POST['email']) ? $_POST['email'] : '';
$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
$dsn = "mysql:host=localhost; dbname=sotu2; charset=utf8";
$username = "root";
$password = "";
try {
    $dbh = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "データベース接続に失敗しました: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$sql = "SELECT * FROM User WHERE email = :email";
$stmt = $dbh->prepare($sql);
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
