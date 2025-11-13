<?php
$u_name = $_POST['u_name'];
$u_name_id = $_POST['u_name_id']; // ← フォームにusernameがある場合
$email = $_POST['email'];
$pwd = password_hash($_POST['pwd'], PASSWORD_DEFAULT);

$dsn = "mysql:host=localhost; dbname=sotu2; charset=utf8";
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

$sql = "SELECT * FROM User WHERE email = :email";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':email', $email);
$stmt->execute();
$member = $stmt->fetch();

if ($member && $member['email'] === $email) {
    $msg = '同じメールアドレスが存在します。';
    $link = '<a href="signup.php">戻る</a>';
} else if($member && $member['u_name_id'] === $u_name_id) {
    $msg = '同じユーザー名が存在します。';
    $link = '<a href="signup.php">戻る</a>';
} else {
    $sql = "INSERT INTO User(email, pwd, u_name, u_name_id) VALUES (:email, :pwd, :u_name, :u_name_id)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':u_name', $u_name);
    $stmt->bindValue(':u_name_id', $u_name_id);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':pwd', $pwd);
    $stmt->execute();

    $msg = '会員登録が完了しました。';
    $link = '<a href="login_form.php">ログインページへ</a>';
}
?>

<?php echo $style; ?>
<h1><?php echo $msg; ?></h1>
<?php echo $link; ?>
