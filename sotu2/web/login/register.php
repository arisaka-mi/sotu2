<?php
$name = $_POST['name'];
$user_name = $_POST['username']; // ← フォームにusernameがある場合
$mail = $_POST['mail'];
$pass = password_hash($_POST['pass'], PASSWORD_DEFAULT);

$dsn = "mysql:host=localhost; dbname=kadai; charset=utf8";
$username = "root";
$password = "";

try {
    $dbh = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    exit('データベース接続失敗: ' . $e->getMessage());
}

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

$sql = "SELECT * FROM users WHERE mail = :mail";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':mail', $mail);
$stmt->execute();
$member = $stmt->fetch();

if ($member && $member['mail'] === $mail) {
    $msg = '同じメールアドレスが存在します。';
    $link = '<a href="signup.php">戻る</a>';
} else if($member && $member['username'] === $user_name) {
    $msg = '同じユーザー名が存在します。';
    $link = '<a href="signup.php">戻る</a>';
} else {
    $sql = "INSERT INTO users(name, username, mail, pass) VALUES (:name, :username, :mail, :pass)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':username', $user_name);
    $stmt->bindValue(':mail', $mail);
    $stmt->bindValue(':pass', $pass);
    $stmt->execute();

    $msg = '会員登録が完了しました。';
    $link = '<a href="login_form.php">ログインページへ</a>';
}
?>

<?php echo $style; ?>
<h1><?php echo $msg; ?></h1>
<?php echo $link; ?>
