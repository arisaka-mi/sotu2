<?php
session_start();
require_once('../login/config.php'); // DB接続

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login_form.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// DBからユーザー情報取得
$stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "ユーザー情報が見つかりません。";
    exit();
}

// エラー初期化
$error = "";
?>

<html>
    <main>

    </main>
    <body>
        <main>
            <a href="body_type.php">骨格診断</a><br>
            <a href="parsonal_color.php">パーソナルカラー診断</a>
        </main>
    </body>
</html>