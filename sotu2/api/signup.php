 <?php
        session_start();
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta  charset="UTF-8">
        <meta  name="description"  content="">
        <meta  name="viewport"  content="width=device-width, initial-scale=1">
        <title>新規登録</title>
    </head>
    <body>
        <main>
            <h1>新規会員登録</h1>
            <form action="register.php" method="post">
                <div>
                <label>ユーザネーム：
                    <input type="text" name="name" required>
                </label>
                </div>
                <div>
                <label>メールアドレス：
                    <input type="text" name="mail" required>
                </label>
                </div>
                <div>
                <label>パスワード：
                    <input type="password" name="pass" required>
                </label>
                </div>
                <input type="submit" value="新規登録">
            </form>
            <p>すでに登録済みの方は<a href="login_from.php">こちら</a></p>
        </main>
    </body>
</html>